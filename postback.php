<?php
/**
 * Secure Advertiser Postback + Affiliate Dispatcher
 * Production Safe Version
 * PHP 7.1+
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

define('APP_INIT', true);
require_once __DIR__ . '/app/config/database.php';

// Assert active non-suspended tenant context
require_tenant();

/* =====================================================
   Helper: Safe Log
   ===================================================== */
function logAdvertiserPostback(PDO $pdo, $raw, $ip, $clickId, $status)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO postback_logs
            (tenant_id, raw_request, ip_address, click_id, status, created_at)
            VALUES (:tenant_id, :raw, INET6_ATON(:ip), :click_id, :status, NOW())
        ");
        $stmt->execute([
            'tenant_id' => current_tenant_id(),
            'raw'       => $raw,
            'ip'        => $ip,
            'click_id'  => $clickId,
            'status'    => $status
        ]);
    } catch (Throwable $e) {
        // Do not break main flow if logging fails
        error_log("Failed to log advertiser postback: " . $e->getMessage());
    }
}

/* =====================================================
   Helper: Fire Affiliate Postback
   ===================================================== */
function fireAffiliatePostback(PDO $pdo, array $data)
{
    $ch = curl_init($data['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO affiliate_postback_logs
            (tenant_id, affiliate_id, offer_id, conversion_id, postback_url, http_code, response, created_at)
            VALUES (?,?,?,?,?,?,?,NOW())
        ");

        $stmt->execute([
            current_tenant_id(),
            $data['affiliate_id'],
            $data['offer_id'],
            $data['conversion_id'],
            $data['url'],
            $httpCode,
            substr((string)$response, 0, 1000)
        ]);
    } catch (Throwable $e) {
        error_log("Failed to insert affiliate postback logs: " . $e->getMessage());
    }
}

/* =====================================================
   Capture Request
   ==================================================== */

$rawRequest = $_SERVER['REQUEST_URI'] ?? '';
$ipAddress  = $_SERVER['REMOTE_ADDR'] ?? '';

$clickId = $_GET['click_id'] ?? '';
$status  = $_GET['status'] ?? 'approved';
$amount  = isset($_GET['amount']) ? (float)$_GET['amount'] : null;
$txid    = $_GET['txid'] ?? null;
$token   = $_GET['token'] ?? '';

if ($clickId === '') {
    http_response_code(400);
    exit('MISSING_CLICK_ID');
}

if (!in_array($status, ['approved','pending','rejected'], true)) {
    $status = 'approved';
}

$tenantId = current_tenant_id();

/* =====================================================
   Start Transaction
   ===================================================== */
$pdo->beginTransaction();

try {

    /* -------------------------------------------------
       Duplicate Protection (tenant scoped)
       -------------------------------------------------- */
    $dup = $pdo->prepare("SELECT conversion_id FROM conversions WHERE click_id=? AND tenant_id=? LIMIT 1");
    $dup->execute([$clickId, $tenantId]);

    if ($dup->fetch()) {
        logAdvertiserPostback($pdo, $rawRequest, $ipAddress, $clickId, 'duplicate');
        $pdo->rollBack();
        exit('DUPLICATE');
    }

    /* -------------------------------------------------
       Fetch Click + Offer + Advertiser (tenant scoped)
       -------------------------------------------------- */
    $stmt = $pdo->prepare("
        SELECT
            c.click_id,
            c.offer_id,
            c.affiliate_id,
            c.sub1, c.sub2, c.sub3, c.sub4, c.sub5,
            c.country,
            c.device,
            o.payout,
            o.revenue,
            o.postback_token,
            o.status AS offer_status,
            o.advertiser_id
        FROM clicks c
        INNER JOIN offers o ON o.offer_id = c.offer_id AND o.tenant_id = c.tenant_id
        WHERE c.click_id = ? AND c.tenant_id = ?
        LIMIT 1
    ");
    $stmt->execute([$clickId, $tenantId]);
    $click = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$click) {
        logAdvertiserPostback($pdo, $rawRequest, $ipAddress, $clickId, 'invalid_click');
        $pdo->rollBack();
        exit('INVALID_CLICK');
    }

    if ($click['offer_status'] !== 'approved' && $click['offer_status'] !== 'active') {
        logAdvertiserPostback($pdo, $rawRequest, $ipAddress, $clickId, 'offer_inactive');
        $pdo->rollBack();
        exit('OFFER_NOT_ACTIVE');
    }

    /* -------------------------------------------------
       Token Validation
       -------------------------------------------------- */
    if (empty($click['postback_token']) ||
        $token === '' ||
        !hash_equals((string)$click['postback_token'], (string)$token)) {

        logAdvertiserPostback($pdo, $rawRequest, $ipAddress, $clickId, 'invalid_token');
        $pdo->rollBack();
        exit('INVALID_TOKEN');
    }

    /* -------------------------------------------------
       Determine Payout
       -------------------------------------------------- */
    $payout = (float)$click['payout'];

    if ($amount !== null && $amount >= 0 && $amount <= ($payout * 2)) {
        $payout = $amount;
    }

    /* -------------------------------------------------
       Insert Conversion (tenant scoped)
       -------------------------------------------------- */
    $insert = $pdo->prepare("
        INSERT INTO conversions
        (tenant_id, click_id, offer_id, affiliate_id, advertiser_id, payout, revenue, status, transaction_id, source, created_at)
        VALUES (?,?,?,?,?,?,?,?,?, 'postback', NOW())
    ");

    $insert->execute([
        $tenantId,
        $clickId,
        $click['offer_id'],
        $click['affiliate_id'],
        $click['advertiser_id'],
        $payout,
        $click['revenue'],
        $status,
        $txid
    ]);

    $conversionId = (int)$pdo->lastInsertId();

    /* -------------------------------------------------
       Credit Affiliate (tenant scoped)
       -------------------------------------------------- */
    if ($status === 'approved') {
        $updUsr = $pdo->prepare("
            UPDATE users
            SET balance = balance + ?
            WHERE user_id = ? AND tenant_id = ?
        ");
        $updUsr->execute([$payout, $click['affiliate_id'], $tenantId]);
    }

    /* -------------------------------------------------
       Affiliate Postback Dispatch (tenant scoped)
       -------------------------------------------------- */
    $pb = $pdo->prepare("
        SELECT * FROM affiliate_offer_postbacks
        WHERE affiliate_id = ? AND offer_id = ? AND status='active' AND tenant_id = ?
        LIMIT 1
    ");
    $pb->execute([$click['affiliate_id'], $click['offer_id'], $tenantId]);
    $postback = $pb->fetch(PDO::FETCH_ASSOC);

    if (!$postback) {
        $pb = $pdo->prepare("
            SELECT * FROM affiliate_postbacks
            WHERE affiliate_id = ? AND status='active' AND tenant_id = ?
            LIMIT 1
        ");
        $pb->execute([$click['affiliate_id'], $tenantId]);
        $postback = $pb->fetch(PDO::FETCH_ASSOC);
    }

    if ($postback && in_array($postback['fire_status'], [$status, 'all'], true)) {

        $tokens = [
            '{click_id}'      => $clickId,
            '{conversion_id}' => $conversionId,
            '{offer_id}'      => $click['offer_id'],
            '{affiliate_id}'  => $click['affiliate_id'],
            '{payout}'        => $payout,
            '{status}'        => $status,
            '{p1}'            => $click['sub1'] ?? '',
            '{p2}'            => $click['sub2'] ?? '',
            '{p3}'            => $click['sub3'] ?? '',
            '{p4}'            => $click['sub4'] ?? '',
            '{p5}'            => $click['sub5'] ?? '',
            '{ip}'            => $ipAddress,
            '{country}'       => $click['country'] ?? '',
            '{device}'        => $click['device'] ?? ''
        ];

        $finalUrl = str_replace(
            array_keys($tokens),
            array_values($tokens),
            $postback['postback_url']
        );

        fireAffiliatePostback($pdo, [
            'affiliate_id'  => $click['affiliate_id'],
            'offer_id'      => $click['offer_id'],
            'conversion_id' => $conversionId,
            'url'           => $finalUrl
        ]);
    }

    /* -------------------------------------------------
       Final Log
       -------------------------------------------------- */
    logAdvertiserPostback($pdo, $rawRequest, $ipAddress, $clickId, 'accepted');

    $pdo->commit();
    exit('OK');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Postback exception: " . $e->getMessage() . " on line " . $e->getLine());
    http_response_code(500);
    exit('SERVER_ERROR');
}
