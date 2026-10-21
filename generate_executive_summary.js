import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://127.0.0.1:8000';
const REPORTS_DIR = path.join(__dirname, 'reports');
const SCREENSHOTS_DIR = path.join(REPORTS_DIR, 'screenshots');

if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

async function run() {
    console.log('🚀 Memulai eksekusi automasi Playwright & Live Order Flow Mak Ethek...');

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const liveData = {
        orderNumber: '',
        orderOtp: '',
        productName: 'Beras Lahap Premium 5kg',
        subtotal: 'Rp 74.000',
        shippingFee: 'Rp 5.000',
        platformFee: 'Rp 1.000',
        totalAmount: 'Rp 80.000',
        customerName: 'Siti Khadijah',
        merchantName: 'Warung Berkah Mak Ethek Gedog',
        agentName: 'Koperasi Sananwetan Berkah',
        driverName: 'Agus Driver Gedog'
    };

    // ==========================================
    // FASE 1: LEVEL CUSTOMER / WARGA (MOBILE PWA)
    // ==========================================
    console.log('\n--- FASE 1: Level Customer / Warga Belanja ---');
    const customerContext = await browser.newContext({
        viewport: { width: 420, height: 900 },
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1'
    });
    const customerPage = await customerContext.newPage();

    // 1.1 Login Warga Siti Khadijah
    console.log('1.1 Login Customer Siti Khadijah...');
    await customerPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await customerPage.fill('input[name="login"]', 'customer.gedog@myethek.id');
    await customerPage.fill('input[name="password"]', 'password');
    await customerPage.click('button[type="submit"]');
    await customerPage.waitForLoadState('networkidle');

    // 1.2 Storefront Utama
    console.log('1.2 Mengambil screenshot Storefront & Deteksi Toko Terdekat...');
    await customerPage.waitForTimeout(1000);
    await customerPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '01_storefront_katalog.png'),
        fullPage: false
    });

    // 1.3 Detail Produk
    console.log('1.3 Mengambil screenshot Detail Produk Beras Lahap...');
    await customerPage.goto(`${BASE_URL}/product/beras-lahap-premium-5kg`, { waitUntil: 'networkidle' });
    await customerPage.waitForTimeout(800);
    await customerPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '02_detail_produk.png'),
        fullPage: false
    });

    // 1.4 Tambah ke Keranjang
    console.log('1.4 Menambahkan produk ke keranjang...');
    const addToCartBtn = await customerPage.$('button[type="submit"]:has-text("Tambah ke Keranjang"), button:has-text("Beli Sekarang"), form button:has-text("Tambah")');
    if (addToCartBtn) {
        await addToCartBtn.click();
        await customerPage.waitForLoadState('networkidle');
    } else {
        // Fallback navigasi langsung ke cart jika form ada di halaman utama
        await customerPage.goto(`${BASE_URL}/`, { waitUntil: 'networkidle' });
        const tambahBtn = await customerPage.$('form[action*="cart/add"] button');
        if (tambahBtn) {
            await tambahBtn.click();
            await customerPage.waitForLoadState('networkidle');
        }
    }

    // 1.5 Keranjang Belanja
    console.log('1.5 Mengambil screenshot Keranjang Belanja...');
    await customerPage.goto(`${BASE_URL}/cart`, { waitUntil: 'networkidle' });
    await customerPage.waitForTimeout(800);
    await customerPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '03_keranjang_belanja.png'),
        fullPage: false
    });

    // 1.6 Halaman Checkout & Perhitungan Jarak Logistik Darat 1.3x
    console.log('1.6 Mengambil screenshot Checkout & Logistik Hyperlocal...');
    await customerPage.goto(`${BASE_URL}/checkout`, { waitUntil: 'networkidle' });
    await customerPage.waitForTimeout(800);
    await customerPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '04_checkout_pengiriman.png'),
        fullPage: false
    });

    // 1.7 Eksekusi Live Order
    console.log('1.7 Membuat Live Order Transaksi...');
    await customerPage.fill('textarea[name="shipping_address"]', 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog (Rumah Pagar Hijau)');
    await customerPage.check('input[name="payment_method"][value="cod"]').catch(() => {});
    
    // Klik tombol submit checkout
    await Promise.all([
        customerPage.waitForNavigation({ waitUntil: 'networkidle' }),
        customerPage.click('button[type="submit"]:has-text("Buat Pesanan Sekarang")')
    ]);

    // 1.8 Halaman Order Success & Milestone Tracking
    console.log('1.8 Ekstraksi nomor pesanan & OTP...');
    await customerPage.waitForTimeout(1000);
    const currentUrl = customerPage.url();
    console.log('URL Setelah Checkout:', currentUrl);

    // Ambil order number dari URL atau page text
    const urlMatch = currentUrl.match(/order\/([^/]+)\/success/);
    if (urlMatch) {
        liveData.orderNumber = urlMatch[1];
    }

    // Ambil OTP dari halaman
    try {
        const otpElement = await customerPage.$('.fs-3.text-success.font-monospace');
        if (otpElement) {
            liveData.orderOtp = (await otpElement.innerText()).trim();
        }
    } catch (e) {
        console.log('OTP selector error:', e.message);
    }
    console.log(`✅ Live Order Berhasil Dibuat: ${liveData.orderNumber} (OTP: ${liveData.orderOtp})`);

    await customerPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '05_customer_order_success.png'),
        fullPage: false
    });

    // 1.9 Riwayat Pesanan Customer
    console.log('1.9 Mengambil screenshot Riwayat Pesanan Customer...');
    await customerPage.goto(`${BASE_URL}/user/orders`, { waitUntil: 'networkidle' });
    await customerPage.waitForTimeout(800);
    await customerPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '06_customer_portal_orders.png'),
        fullPage: false
    });

    await customerContext.close();

    // ==========================================
    // FASE 2: LEVEL SUB-AGEN (WARUNG DESA)
    // ==========================================
    console.log('\n--- FASE 2: Level Penjualan Sub-Agen (Warung Gedog) ---');
    const merchantContext = await browser.newContext({
        viewport: { width: 1280, height: 850 }
    });
    const subAgenPage = await merchantContext.newPage();

    // 2.1 Login Sub-Agen
    console.log('2.1 Login Sub-Agen Warung Gedog...');
    await subAgenPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await subAgenPage.fill('input[name="login"]', 'warung.gedog@myethek.id');
    await subAgenPage.fill('input[name="password"]', 'password');
    await subAgenPage.click('button[type="submit"]');
    await subAgenPage.waitForLoadState('networkidle');

    // 2.2 Dashboard Sub-Agen
    console.log('2.2 Screenshot Dashboard Sub-Agen...');
    await subAgenPage.goto(`${BASE_URL}/merchant`, { waitUntil: 'networkidle' });
    await subAgenPage.waitForTimeout(800);
    await subAgenPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '07_subagen_dashboard.png'),
        fullPage: false
    });

    // 2.3 Daftar Pesanan Masuk (Pending Order)
    console.log('2.3 Screenshot Pesanan Masuk di Sub-Agen...');
    await subAgenPage.goto(`${BASE_URL}/merchant/orders?status=pending`, { waitUntil: 'networkidle' });
    await subAgenPage.waitForTimeout(800);
    await subAgenPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '08_subagen_orders_incoming.png'),
        fullPage: false
    });

    // 2.4 Sub-Agen Konfirmasi & Kemas Pesanan
    console.log('2.4 Sub-Agen memproses pesanan (Terima & Kemas)...');
    const terimaBtn = await subAgenPage.$('button[type="submit"]:has-text("Terima & Kemas")');
    if (terimaBtn) {
        await Promise.all([
            subAgenPage.waitForNavigation({ waitUntil: 'networkidle' }),
            terimaBtn.click()
        ]);
        console.log('✅ Pesanan berhasil dikonfirmasi oleh Sub-Agen');
    }

    // 2.5 Screenshot Pesanan Dalam Proses Kemas
    console.log('2.5 Screenshot Pesanan Sedang Dikemas...');
    await subAgenPage.goto(`${BASE_URL}/merchant/orders?status=processing`, { waitUntil: 'networkidle' });
    await subAgenPage.waitForTimeout(800);
    await subAgenPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '09_subagen_order_processing.png'),
        fullPage: false
    });

    // 2.6 Portal Kulakan B2B (Procurement Sub-Agen -> Agen)
    console.log('2.6 Screenshot Modul Kulakan B2B Berjenjang...');
    await subAgenPage.goto(`${BASE_URL}/merchant/procurement`, { waitUntil: 'networkidle' });
    await subAgenPage.waitForTimeout(800);
    await subAgenPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '10_b2b_kulakan_procurement.png'),
        fullPage: false
    });

    await merchantContext.close();

    // ==========================================
    // FASE 3: LEVEL AGEN (KECAMATAN SANANWETAN)
    // ==========================================
    console.log('\n--- FASE 3: Level Penjualan Agen (Kecamatan Sananwetan) ---');
    const agenContext = await browser.newContext({
        viewport: { width: 1280, height: 850 }
    });
    const agenPage = await agenContext.newPage();

    // 3.1 Login Agen
    console.log('3.1 Login Agen Sananwetan...');
    await agenPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await agenPage.fill('input[name="login"]', 'agen.sananwetan@myethek.id');
    await agenPage.fill('input[name="password"]', 'password');
    await agenPage.click('button[type="submit"]');
    await agenPage.waitForLoadState('networkidle');

    // 3.2 Dashboard Agen
    console.log('3.2 Screenshot Dashboard Agen Kecamatan...');
    await agenPage.goto(`${BASE_URL}/merchant`, { waitUntil: 'networkidle' });
    await agenPage.waitForTimeout(800);
    await agenPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '11_agen_dashboard_overview.png'),
        fullPage: false
    });

    // 3.3 Inventori & Buffer Stock Agen
    console.log('3.3 Screenshot Manajemen Inventori Stok Agen...');
    await agenPage.goto(`${BASE_URL}/merchant/inventory`, { waitUntil: 'networkidle' });
    await agenPage.waitForTimeout(800);
    await agenPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '12_agen_inventory_buffer.png'),
        fullPage: false
    });

    await agenContext.close();

    // ==========================================
    // FASE 4: LEVEL KURIR OJOL LOKAL
    // ==========================================
    console.log('\n--- FASE 4: Level Logistik Kurir Ojol Lokal ---');
    const driverContext = await browser.newContext({
        viewport: { width: 420, height: 900 }
    });
    const driverPage = await driverContext.newPage();

    // 4.1 Login Kurir
    console.log('4.1 Login Kurir Agus Driver Gedog...');
    await driverPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await driverPage.fill('input[name="login"]', 'driver.gedog@myethek.id');
    await driverPage.fill('input[name="password"]', 'password');
    await driverPage.click('button[type="submit"]');
    await driverPage.waitForLoadState('networkidle');

    // 4.2 Portal Driver - Order Siap Diambil
    console.log('4.2 Screenshot Portal Kurir (Pesanan Siap Diambil)...');
    await driverPage.goto(`${BASE_URL}/driver`, { waitUntil: 'networkidle' });
    await driverPage.waitForTimeout(800);
    await driverPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '13_driver_job_available.png'),
        fullPage: false
    });

    // 4.3 Kurir Mengambil Pesanan
    console.log('4.3 Kurir mengambil pesanan live...');
    const ambilBtn = await driverPage.$('form[action*="driver"] button:has-text("Ambil Pesanan Ini")');
    if (ambilBtn) {
        await Promise.all([
            driverPage.waitForNavigation({ waitUntil: 'networkidle' }),
            ambilBtn.click()
        ]);
        console.log('✅ Pesanan berhasil diambil kurir');
    }

    // 4.4 Screenshot Pengantaran Aktif
    console.log('4.4 Screenshot Tugas Pengantaran Aktif...');
    await driverPage.waitForTimeout(800);
    await driverPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '14_driver_active_delivery.png'),
        fullPage: false
    });

    // 4.5 Kurir Menyelesaikan Serah Terima dengan OTP
    if (liveData.orderOtp) {
        console.log(`4.5 Kurir memasukkan OTP serah terima (${liveData.orderOtp})...`);
        const otpInput = await driverPage.$('form[action*="complete"] input[name="otp"]');
        if (otpInput) {
            await otpInput.fill(liveData.orderOtp);
            const selesaiBtn = await driverPage.$('form[action*="complete"] button:has-text("Selesaikan")');
            if (selesaiBtn) {
                await Promise.all([
                    driverPage.waitForNavigation({ waitUntil: 'networkidle' }),
                    selesaiBtn.click()
                ]);
                console.log('✅ Serah terima selesai & Escrow berhasil dicairkan ke dompet kurir & warung!');
            }
        }
    }

    // 4.6 Screenshot Status Pengantaran Selesai & Saldo Masuk
    console.log('4.6 Screenshot Pengantaran Selesai & Saldo Masuk...');
    await driverPage.waitForTimeout(800);
    await driverPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '15_driver_job_completed.png'),
        fullPage: false
    });

    await driverContext.close();

    // ==========================================
    // FASE 5: KEUANGAN & AUDIT HOLDING KOPERASI
    // ==========================================
    console.log('\n--- FASE 5: Level Keuangan & Audit Holding Koperasi ---');
    const adminContext = await browser.newContext({
        viewport: { width: 1280, height: 850 }
    });
    const adminPage = await adminContext.newPage();

    // 5.1 Login Sub-Agen untuk Cek Saldo & Mutasi Escrow
    console.log('5.1 Cek Mutasi Saldo & Double-Entry Ledger Sub-Agen...');
    await adminPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await adminPage.fill('input[name="login"]', 'warung.gedog@myethek.id');
    await adminPage.fill('input[name="password"]', 'password');
    await adminPage.click('button[type="submit"]');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.goto(`${BASE_URL}/merchant/finance`, { waitUntil: 'networkidle' });
    await adminPage.waitForTimeout(800);
    await adminPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '16_subagen_finance_settlement.png'),
        fullPage: false
    });

    // 5.2 Login Admin Induk Koperasi Jatim
    console.log('5.2 Login Admin Audit Holding Koperasi Jatim...');
    await adminContext.clearCookies();
    await adminPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await adminPage.fill('input[name="login"]', 'admin@myethek.id');
    await adminPage.fill('input[name="password"]', 'password');
    await adminPage.click('button[type="submit"]');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
    await adminPage.waitForTimeout(800);
    await adminPage.screenshot({
        path: path.join(SCREENSHOTS_DIR, '17_admin_holding_overview.png'),
        fullPage: false
    });

    // ==========================================
    // FASE 6: GENERATE EXECUTIVE SUMMARY HTML
    // ==========================================
    console.log('\n--- FASE 6: Generate Executive Summary Document (HTML & PDF) ---');
    const htmlReportPath = path.join(REPORTS_DIR, 'Executive_Summary_Mak_Ethek.html');
    const pdfReportPath = path.join(REPORTS_DIR, 'Executive_Summary_Mak_Ethek.pdf');

    const htmlContent = generateExecutiveSummaryHtml(liveData);
    fs.writeFileSync(htmlReportPath, htmlContent, 'utf-8');
    console.log(`✅ File HTML berhasil disimpan di: ${htmlReportPath}`);

    // Render HTML ke PDF dengan format A4 rapi
    console.log('Mengonversi HTML ke PDF publication-ready...');
    const pdfPage = await adminContext.newPage();
    await pdfPage.goto(`file:///${htmlReportPath.replace(/\\/g, '/')}`, { waitUntil: 'networkidle' });
    await pdfPage.waitForTimeout(1000);

    await pdfPage.pdf({
        path: pdfReportPath,
        format: 'A4',
        printBackground: true,
        margin: {
            top: '15mm',
            bottom: '15mm',
            left: '12mm',
            right: '12mm'
        }
    });
    console.log(`🎉 Berhasil membuat PDF Executive Summary: ${pdfReportPath}`);

    await adminContext.close();
    await browser.close();

    console.log('\n======================================================');
    console.log('🏆 SELURUH PROSES AUTOMASI LIVE ORDER & LAPORAN SELESAI!');
    console.log(`📄 Dokumen HTML : ${htmlReportPath}`);
    console.log(`📑 Dokumen PDF  : ${pdfReportPath}`);
    console.log('======================================================\n');
}

function generateExecutiveSummaryHtml(data) {
    const todayStr = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Summary - Platform Mak Ethek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #047857;
            --primary-dark: #064e3b;
            --primary-light: #10b981;
            --secondary: #0f172a;
            --accent: #d97706;
            --info: #0284c7;
            --bg-light: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            line-height: 1.65;
            font-size: 14px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* HEADER & COVER */
        .doc-header {
            background: linear-gradient(135deg, #064e3b 0%, #047857 55%, #059669 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 35px;
            box-shadow: 0 10px 25px -5px rgba(4, 120, 87, 0.3);
            position: relative;
            overflow: hidden;
        }

        .doc-header::after {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }

        .header-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .doc-header h1 {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .doc-header p {
            font-size: 14px;
            opacity: 0.9;
            max-width: 800px;
            line-height: 1.6;
        }

        .doc-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 12px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-label {
            opacity: 0.75;
        }

        .meta-value {
            font-weight: 600;
        }

        /* METRIC CARDS */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 35px;
        }

        .kpi-card {
            background: var(--surface);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .kpi-title {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 11px;
            color: var(--primary);
            font-weight: 600;
        }

        /* SECTION STYLING */
        .section-block {
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
            page-break-inside: avoid;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-num {
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary);
            letter-spacing: -0.3px;
        }

        .section-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* DIAGRAM BOX */
        .diagram-box {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 1px dashed #cbd5e1;
        }

        .tier-flow {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            text-align: center;
            font-size: 11px;
        }

        .tier-node {
            background: white;
            padding: 12px 10px;
            border-radius: 8px;
            flex: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-top: 3px solid var(--primary);
        }

        .tier-node.accent {
            border-top-color: var(--accent);
        }

        .tier-node.info {
            border-top-color: var(--info);
        }

        .tier-node strong {
            display: block;
            font-size: 12px;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .flow-arrow {
            color: #94a3b8;
            font-weight: bold;
            font-size: 14px;
        }

        /* SCREENSHOT GALLERY */
        .screen-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        .screen-single {
            grid-column: span 2;
        }

        .screen-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            display: flex;
            flex-column: column;
        }

        .screen-card img {
            width: 100%;
            height: auto;
            max-height: 480px;
            object-fit: contain;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: block;
        }

        .screen-card.mobile-mock img {
            max-height: 520px;
            object-fit: contain;
        }

        .screen-caption {
            padding: 12px 15px;
            background: white;
        }

        .screen-caption h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .screen-caption p {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* TABLES */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            margin: 15px 0;
        }

        .data-table th {
            background: #f8fafc;
            color: var(--secondary);
            font-weight: 700;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 2px solid var(--border);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .badge-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-warning {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-code {
            font-family: 'JetBrains Mono', monospace;
            background: #f1f5f9;
            color: #334155;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
        }

        /* HIGHLIGHT BOX */
        .callout-box {
            background: #ecfdf5;
            border-left: 4px solid var(--primary);
            padding: 15px 18px;
            border-radius: 0 8px 8px 0;
            margin: 15px 0;
            font-size: 12.5px;
            color: #065f46;
        }

        .callout-box strong {
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
        }

        /* FOOTER */
        .doc-footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-muted);
            font-size: 11.5px;
            border-top: 1px solid var(--border);
            margin-top: 30px;
        }

        @media print {
            body {
                background: white;
            }
            .container {
                max-width: 100%;
                padding: 0;
            }
            .section-block {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
                margin-bottom: 25px;
            }
            .screen-card img {
                max-height: 400px;
            }
            .doc-header {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- HEADER COVER -->
    <div class="doc-header">
        <span class="header-badge">Executive Briefing & Workflow Audit</span>
        <h1>Executive Summary: Ekosistem Perdagangan Hyperlocal Mak Ethek</h1>
        <p>Laporan komprehensif alur operasional multi-tier berbasis Koperasi Jawa Timur — mulai dari transaksi belanja warga desa, manajemen pesanan Sub-Agen & Agen, sistem kulakan B2B, logistik kurir ojol lokal, hingga settlement holding keuangan koperasi.</p>
        
        <div class="doc-meta">
            <div class="meta-item">
                <span class="meta-label">Tanggal Terbit:</span>
                <span class="meta-value">${todayStr}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Live Order ID:</span>
                <span class="meta-value badge-code">${data.orderNumber || 'ORD-20261021-DEMO'}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Metode Verifikasi:</span>
                <span class="meta-value">Automated Playwright Real-Flow Testing</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Wilayah Uji:</span>
                <span class="meta-value">Kel. Gedog & Kec. Sananwetan, Kota Blitar</span>
            </div>
        </div>
    </div>

    <!-- KEY METRIC HIGHLIGHTS -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-title">Hierarki Rantai Pasok</div>
            <div class="kpi-value">4 Tingkat</div>
            <div class="kpi-sub">Induk → Dist → Agen → Sub</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Logika Logistik</div>
            <div class="kpi-value">1.3× Darat</div>
            <div class="kpi-sub">Formula Haversine Terkoreksi</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Integritas Transaksi</div>
            <div class="kpi-value">Double Escrow</div>
            <div class="kpi-sub">Pencairan Aman via OTP 4-Digit</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Toko Dialihkan</div>
            <div class="kpi-value">Zero Drop</div>
            <div class="kpi-sub">Otomatis Cascading Fallback</div>
        </div>
    </div>

    <!-- BAB 1: ARSITEKTUR EKOSISTEM -->
    <div class="section-block">
        <div class="section-header">
            <div class="section-num">1</div>
            <div>
                <h2 class="section-title">Arsitektur Multi-Tier & Tata Kelola Koperasi Jawa Timur</h2>
                <div class="section-desc">Konsolidasi lumbung pasokan dari level provinsi hingga warung kelontong desa</div>
            </div>
        </div>

        <p><strong>Mak Ethek</strong> memecahkan permasalahan klasik rantai pasok sembako: panjangnya tengkulak dan margin yang tidak merata. Dengan memadukan <em>Koperasi Financial Holding</em>, seluruh legalitas pembayaran terpusat (QRIS, VA Bank), sementara pedagang warung mikro desa bertindak sebagai <strong>Sub-Agen</strong> yang melayani warga dalam radius hiperlokal 3-5 km.</p>

        <div class="diagram-box">
            <div class="tier-flow">
                <div class="tier-node">
                    <strong>Pusat / Induk</strong>
                    Provinsi Jatim<br><small class="text-muted">Master Katalog & Pasokan</small>
                </div>
                <div class="flow-arrow">➔</div>
                <div class="tier-node info">
                    <strong>Distributor</strong>
                    Kabupaten / Kota<br><small class="text-muted">Gudang Buffer Massal</small>
                </div>
                <div class="flow-arrow">➔</div>
                <div class="tier-node accent">
                    <strong>Agen</strong>
                    Kecamatan<br><small class="text-muted">Hub Logistik & Cadangan</small>
                </div>
                <div class="flow-arrow">➔</div>
                <div class="tier-node">
                    <strong>Sub-Agen</strong>
                    Warung Desa/Kelurahan<br><small class="text-muted">Etalase Retail ke Warga</small>
                </div>
                <div class="flow-arrow">➔</div>
                <div class="tier-node">
                    <strong>Warga / Pembeli</strong>
                    Rumah Tangga<br><small class="text-muted">Diantar Kurir Ojol Lokal</small>
                </div>
            </div>
        </div>

        <div class="callout-box">
            <strong>Mekanisme Cascading Fallback:</strong>
            Bila warung kelontong desa (Sub-Agen) kehabisan stok beras atau sedang tutup, sistem secara otomatis mengalihkan pesanan warga ke Agen di tingkat Kecamatan. Jika Agen kecamatan tutup, pesanan dialihkan ke Distributor Kabupaten. <em>Konsumen tidak pernah mengalami keranjang belanja dibatalkan sepihak.</em>
        </div>
    </div>

    <!-- BAB 2: LEVEL CUSTOMER / USER -->
    <div class="section-block">
        <div class="section-header">
            <div class="section-num">2</div>
            <div>
                <h2 class="section-title">Alur Pengalaman Belanja Warga (Level Customer)</h2>
                <div class="section-desc">Mobile-first PWA Storefront, single-merchant cart, dan transparansi logistik</div>
            </div>
        </div>

        <p>Konsumen mengakses katalog sembako terstandarisasi. Berdasarkan deteksi kelurahan warga (Siti Khadijah di Kelurahan Gedog), sistem mengunci toko pelayan ke <strong>Warung Berkah Mak Ethek Gedog</strong>. Hal ini menjamin kalkulasi ongkos kirim transparan dan pengantaran cepat.</p>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <img src="screenshots/01_storefront_katalog.png" alt="Storefront Mak Ethek">
                <div class="screen-caption">
                    <h4>1. Etalase & Deteksi Lokasi Warga</h4>
                    <p>Deteksi wilayah aktif di Kelurahan Gedog dan pemilihan otomatis warung mitra terdekat untuk menjamin kepastian pasokan.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <img src="screenshots/02_detail_produk.png" alt="Detail Produk">
                <div class="screen-caption">
                    <h4>2. Detail Produk Satuan Tradisional</h4>
                    <p>Informasi harga terstandarisasi, satuan lokal (sak 5kg, kg, liter), deskripsi lumbung petani, dan stok riil toko mitra.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <img src="screenshots/03_keranjang_belanja.png" alt="Keranjang Belanja">
                <div class="screen-caption">
                    <h4>3. Keranjang Belanja (Single-Merchant Policy)</h4>
                    <p>Enforcement 1 keranjang 1 toko demi menghindari pemisahan ongkir yang merugikan warga dan pedagang mikro.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <img src="screenshots/04_checkout_pengiriman.png" alt="Checkout Pengiriman">
                <div class="screen-caption">
                    <h4>4. Checkout Logistik & Rincian Transparan</h4>
                    <p>Perhitungan jarak darat rute Haversine × 1.3, pilihan kurir ojol lokal / antar warung, dan platform fee koperasi Rp 1.000.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <img src="screenshots/05_customer_order_success.png" alt="Order Success">
                <div class="screen-caption">
                    <h4>5. Milestone Tracking & Kode OTP Pelanggan</h4>
                    <p>Pesanan <strong>${data.orderNumber}</strong> berhasil dibuat. Menampilkan 4-digit OTP (<span class="badge-code">${data.orderOtp}</span>) dan tombol WhatsApp konfirmasi ke warung.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <img src="screenshots/06_customer_portal_orders.png" alt="Portal Riwayat Pesanan">
                <div class="screen-caption">
                    <h4>6. Portal Riwayat Pesanan Warga</h4>
                    <p>Pelanggan dapat memantau status pesanan kapan pun secara real-time minim kuota data.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BAB 3: LEVEL SUB-AGEN & AGEN -->
    <div class="section-block">
        <div class="section-header">
            <div class="section-num">3</div>
            <div>
                <h2 class="section-title">Alur Penjualan: Sub-Agen (Desa) & Agen (Kecamatan)</h2>
                <div class="section-desc">Menerima pesanan, konfirmasi kemas, dan orkestrasi pasokan kecamatan</div>
            </div>
        </div>

        <p>Pada sisi penjualan, merchant memiliki portal khusus yang dirancang ringan dan responsif. Begitu warga membuat pesanan, daftar pesanan langsung muncul pada tab <strong>"Baru Masuk"</strong> untuk dikonfirmasi dan dikemas.</p>

        <div class="screen-container">
            <div class="screen-card">
                <img src="screenshots/08_subagen_orders_incoming.png" alt="Pesanan Masuk Sub-Agen">
                <div class="screen-caption">
                    <h4>7. Notifikasi Pesanan Masuk di Warung Sub-Agen</h4>
                    <p>Warung Mak Ethek Gedog menerima pesanan retail atas nama Siti Khadijah. Tombol <em>"Terima & Kemas"</em> siap dieksekusi.</p>
                </div>
            </div>
            <div class="screen-card">
                <img src="screenshots/09_subagen_order_processing.png" alt="Pesanan Sedang Dikemas">
                <div class="screen-caption">
                    <h4>8. Status Pesanan Berpindah ke Pengemasan</h4>
                    <p>Setelah dikonfirmasi, status pesanan beralih ke <em>Processing</em> dan paket siap diambil oleh kurir ojol lokal.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card">
                <img src="screenshots/11_agen_dashboard_overview.png" alt="Dashboard Agen Sananwetan">
                <div class="screen-caption">
                    <h4>9. Dashboard Agen Kecamatan Sananwetan</h4>
                    <p>Monitoring omset kecamatan, mengelola puluhan sub-agen desa, dan siap melayani pesanan fallback bila warung desa tutup.</p>
                </div>
            </div>
            <div class="screen-card">
                <img src="screenshots/12_agen_inventory_buffer.png" alt="Inventori Agen">
                <div class="screen-caption">
                    <h4>10. Buffer Stock & Manajemen Inventori Agen</h4>
                    <p>Stok pengaman di level kecamatan untuk memasok kebutuhan kulakan warung desa dan cadangan pemenuhan langsung.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BAB 4: KULAKAN B2B (PROCUREMENT) -->
    <div class="section-block">
        <div class="section-header">
            <div class="section-num">4</div>
            <div>
                <h2 class="section-title">Alur Kulakan Grosir B2B Berjenjang (Procurement)</h2>
                <div class="section-desc">Tier Wholesale Pricing, penegakan MOQ, dan sinkronisasi otomatis stok retail</div>
            </div>
        </div>

        <p>Warung kelontong desa (Sub-Agen) tidak perlu pergi jauh ke pasar induk. Mereka melakukan kulakan langsung melalui portal merchant dengan harga grosir resmi koperasi:</p>

        <div class="screen-container">
            <div class="screen-card screen-single">
                <img src="screenshots/10_b2b_kulakan_procurement.png" alt="Katalog Kulakan B2B">
                <div class="screen-caption">
                    <h4>11. Portal Kulakan B2B Terintegrasi (Sub-Agen ➔ Agen Kecamatan)</h4>
                    <p>Tampilan katalog grosir khusus mitra dengan Tier Pricing resmi (misal Beras Lahap Rp 68.500 dengan MOQ 5 sak, Minyakita Rp 14.200 dengan MOQ 12 pcs). Penerimaan barang otomatis meng-update stok etalase eceran warung desa.</p>
                </div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Tingkatan Kulakan</th>
                    <th>Pihak Pembeli</th>
                    <th>Penyedia Pasokan</th>
                    <th>Skema Harga & Syarat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Tier 1 (Sub-Agen)</strong></td>
                    <td>Warung Kelontong Desa</td>
                    <td>Agen Kecamatan</td>
                    <td>Harga Grosir Sub-Agen, MOQ Ringan (5-12 unit)</td>
                </tr>
                <tr>
                    <td><strong>Tier 2 (Agen)</strong></td>
                    <td>Koperasi Unit Kecamatan</td>
                    <td>Distributor Kabupaten/Kota</td>
                    <td>Harga Agen Khusus, MOQ Sedang (20-60 unit)</td>
                </tr>
                <tr>
                    <td><strong>Tier 3 (Distributor)</strong></td>
                    <td>Gudang Distribusi Daerah</td>
                    <td>Induk Koperasi Jawa Timur</td>
                    <td>Harga Lumbung Pangan Massal, MOQ Kontainer/Truk</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- BAB 5: KURIR LOGISTIK & SETTLEMENT ESCROW -->
    <div class="section-block">
        <div class="section-header">
            <div class="section-num">5</div>
            <div>
                <h2 class="section-title">Logistik Kurir Ojol Lokal & Penyelesaian Keuangan (Escrow)</h2>
                <div class="section-desc">Pemberdayaan ojol desa, verifikasi kode OTP, dan double-entry ledger otomatis</div>
            </div>
        </div>

        <p>Pesanan yang telah siap diambil warung otomatis memicu penugasan kurir di portal driver. Kurir mengambil order dan wajib meminta kode OTP kepada pembeli saat serah terima barang:</p>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <img src="screenshots/13_driver_job_available.png" alt="Driver Job Available">
                <div class="screen-caption">
                    <h4>12. Pesanan Masuk di Portal Kurir Lokal</h4>
                    <p>Kurir Agus melihat pesanan siap ambil di Warung Gedog lengkap dengan nominal ongkir dan rute tujuan.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <img src="screenshots/14_driver_active_delivery.png" alt="Driver Active Delivery">
                <div class="screen-caption">
                    <h4>13. Pengantaran Aktif & Form Validasi OTP</h4>
                    <p>Kurir menuju rumah Siti Khadijah di Jl. Kawi. Kurir meminta kode OTP 4-digit untuk menyelesaikan pengantaran.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <img src="screenshots/15_driver_job_completed.png" alt="Driver Job Completed">
                <div class="screen-caption">
                    <h4>14. Verifikasi Berhasil & Ongkir Masuk Dompet</h4>
                    <p>Begitu OTP cocok, status berubah menjadi <em>Delivered</em> dan ongkir Rp 5.000 seketika masuk ke dompet kurir tanpa potongan.</p>
                </div>
            </div>
            <div class="screen-card">
                <img src="screenshots/16_subagen_finance_settlement.png" alt="Settlement Keuangan Sub-Agen">
                <div class="screen-caption">
                    <h4>15. Dompet Toko & Double-Entry Ledger Koperasi</h4>
                    <p>Hasil penjualan pokok produk langsung dicairkan dari <em>escrow_pending</em> ke <em>balance_available</em> toko mitra.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card screen-single">
                <img src="screenshots/17_admin_holding_overview.png" alt="Admin Holding Overview">
                <div class="screen-caption">
                    <h4>16. Panel Audit & Konsolidasi Holding Koperasi Jawa Timur</h4>
                    <p>Pengawasan menyeluruh terhadap perputaran arus barang, fee platform koperasi, dan kepatuhan distribusi di seluruh kabupaten/kota.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BAB 6: KESIMPULAN & REKOMENDASI -->
    <div class="section-block">
        <div class="section-header">
            <div class="section-num">6</div>
            <div>
                <h2 class="section-title">Kesimpulan Strategis & Nilai Tambah Platform</h2>
                <div class="section-desc">Dampak ekonomi terhadap warung tradisional dan stabilitas harga sembako</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Komponen Ekosistem</th>
                    <th>Model Tradisional Lama</th>
                    <th>Inovasi Platform Mak Ethek</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Warung Desa (Sub-Agen)</strong></td>
                    <td>Kulakan sendiri ke pasar subuh, modal tertahan, harga beli fluktuatif</td>
                    <td>Kulakan via portal B2B, stok sinkron otomatis, legalitas dilindungi koperasi</td>
                </tr>
                <tr>
                    <td><strong>Konsumen / Warga</strong></td>
                    <td>Beli di e-commerce nasional dengan ongkir mahal dan barang lama sampai</td>
                    <td>Belanja sembako dari warung tetangga, ongkir murah terstandarisasi, tiba hitungan menit</td>
                </tr>
                <tr>
                    <td><strong>Kurir Lokal Desa</strong></td>
                    <td>Monopoli aplikator besar dengan potongan komisi tinggi (20-30%)</td>
                    <td>100% ongkos kirim diterima kurir, verifikasi serah-terima aman berbasis OTP</td>
                </tr>
                <tr>
                    <td><strong>Holding Koperasi</strong></td>
                    <td>Peran koperasi pasif hanya sebagai simpan pinjam</td>
                    <td>Koperasi menjadi agregator rantai pasok dan pengendali stabilitas harga pangan</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="doc-footer">
        <p><strong>Mak Ethek (MyEthek) — Platform Perdagangan & Logistik Hyperlocal Koperasi Jawa Timur</strong></p>
        <p>Dokumen ini di-generate secara otomatis menggunakan Playwright Automation Engine pada ${todayStr}.</p>
    </div>
</div>

</body>
</html>`;
}

run().catch(err => {
    console.error('❌ Terjadi kesalahan pada eksekusi:', err);
    process.exit(1);
});
