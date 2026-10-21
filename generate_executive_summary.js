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

// Cek apakah user menjalankan dengan argumen --render-only
const isRenderOnly = process.argv.includes('--render-only');

async function run() {
    console.log('🚀 Memulai eksekusi automasi Mak Ethek Executive Summary Generator...');

    const liveData = {
        orderNumber: 'ORD-20261021-FUACF',
        orderOtp: '4500',
        productName: 'Beras Lahap Premium 5kg',
        subtotal: 'Rp 74.000',
        shippingFee: 'Rp 6.000',
        platformFee: 'Rp 1.000',
        totalAmount: 'Rp 81.000',
        customerName: 'Siti Khadijah',
        merchantName: 'Warung Berkah Mak Ethek Gedog',
        agentName: 'Koperasi Sananwetan Berkah',
        driverName: 'Agus Driver Gedog'
    };

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    if (!isRenderOnly) {
        // ==========================================
        // FASE 1: LEVEL CUSTOMER / WARGA (MOBILE PWA)
        // ==========================================
        console.log('\n--- FASE 1: Level Customer / Warga Belanja ---');
        const customerContext = await browser.newContext({
            viewport: { width: 420, height: 900 },
            userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1'
        });
        const customerPage = await customerContext.newPage();

        console.log('1.1 Login Customer Siti Khadijah...');
        await customerPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        await customerPage.fill('input[name="login"]', 'customer.gedog@myethek.id');
        await customerPage.fill('input[name="password"]', 'password');
        await customerPage.click('button[type="submit"]');
        await customerPage.waitForLoadState('networkidle');

        console.log('1.2 Mengambil screenshot Storefront & Deteksi Toko Terdekat...');
        await customerPage.waitForTimeout(800);
        await customerPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '01_storefront_katalog.png'),
            fullPage: false
        });

        console.log('1.3 Mengambil screenshot Detail Produk Beras Lahap...');
        await customerPage.goto(`${BASE_URL}/product/beras-lahap-premium-5kg`, { waitUntil: 'networkidle' });
        await customerPage.waitForTimeout(800);
        await customerPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '02_detail_produk.png'),
            fullPage: false
        });

        console.log('1.4 Menambahkan produk ke keranjang...');
        const addToCartBtn = await customerPage.$('button[type="submit"]:has-text("Tambah ke Keranjang"), button:has-text("Beli Sekarang"), form button:has-text("Tambah")');
        if (addToCartBtn) {
            await addToCartBtn.click();
            await customerPage.waitForLoadState('networkidle');
        } else {
            await customerPage.goto(`${BASE_URL}/`, { waitUntil: 'networkidle' });
            const tambahBtn = await customerPage.$('form[action*="cart/add"] button');
            if (tambahBtn) {
                await tambahBtn.click();
                await customerPage.waitForLoadState('networkidle');
            }
        }

        console.log('1.5 Mengambil screenshot Keranjang Belanja...');
        await customerPage.goto(`${BASE_URL}/cart`, { waitUntil: 'networkidle' });
        await customerPage.waitForTimeout(800);
        await customerPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '03_keranjang_belanja.png'),
            fullPage: false
        });

        console.log('1.6 Mengambil screenshot Checkout & Logistik Hyperlocal...');
        await customerPage.goto(`${BASE_URL}/checkout`, { waitUntil: 'networkidle' });
        await customerPage.waitForTimeout(800);
        await customerPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '04_checkout_pengiriman.png'),
            fullPage: false
        });

        console.log('1.7 Membuat Live Order Transaksi...');
        await customerPage.fill('textarea[name="shipping_address"]', 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog (Rumah Pagar Hijau)');
        await customerPage.check('input[name="payment_method"][value="cod"]').catch(() => {});
        
        await Promise.all([
            customerPage.waitForNavigation({ waitUntil: 'networkidle' }),
            customerPage.click('button[type="submit"]:has-text("Buat Pesanan Sekarang")')
        ]);

        console.log('1.8 Ekstraksi nomor pesanan & OTP...');
        await customerPage.waitForTimeout(1000);
        const currentUrl = customerPage.url();
        const urlMatch = currentUrl.match(/order\/([^/]+)\/success/);
        if (urlMatch) {
            liveData.orderNumber = urlMatch[1];
        }

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

        console.log('2.1 Login Sub-Agen Warung Gedog...');
        await subAgenPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        await subAgenPage.fill('input[name="login"]', 'warung.gedog@myethek.id');
        await subAgenPage.fill('input[name="password"]', 'password');
        await subAgenPage.click('button[type="submit"]');
        await subAgenPage.waitForLoadState('networkidle');

        console.log('2.2 Screenshot Dashboard Sub-Agen...');
        await subAgenPage.goto(`${BASE_URL}/merchant`, { waitUntil: 'networkidle' });
        await subAgenPage.waitForTimeout(800);
        await subAgenPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '07_subagen_dashboard.png'),
            fullPage: false
        });

        console.log('2.3 Screenshot Pesanan Masuk di Sub-Agen...');
        await subAgenPage.goto(`${BASE_URL}/merchant/orders?status=pending`, { waitUntil: 'networkidle' });
        await subAgenPage.waitForTimeout(800);
        await subAgenPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '08_subagen_orders_incoming.png'),
            fullPage: false
        });

        console.log('2.4 Sub-Agen memproses pesanan (Terima & Kemas)...');
        const terimaBtn = await subAgenPage.$('button[type="submit"]:has-text("Terima & Kemas")');
        if (terimaBtn) {
            await Promise.all([
                subAgenPage.waitForNavigation({ waitUntil: 'networkidle' }),
                terimaBtn.click()
            ]);
            console.log('✅ Pesanan berhasil dikonfirmasi oleh Sub-Agen');
        }

        console.log('2.5 Screenshot Pesanan Sedang Dikemas...');
        await subAgenPage.goto(`${BASE_URL}/merchant/orders?status=processing`, { waitUntil: 'networkidle' });
        await subAgenPage.waitForTimeout(800);
        await subAgenPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '09_subagen_order_processing.png'),
            fullPage: false
        });

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

        console.log('3.1 Login Agen Sananwetan...');
        await agenPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        await agenPage.fill('input[name="login"]', 'agen.sananwetan@myethek.id');
        await agenPage.fill('input[name="password"]', 'password');
        await agenPage.click('button[type="submit"]');
        await agenPage.waitForLoadState('networkidle');

        console.log('3.2 Screenshot Dashboard Agen Kecamatan...');
        await agenPage.goto(`${BASE_URL}/merchant`, { waitUntil: 'networkidle' });
        await agenPage.waitForTimeout(800);
        await agenPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '11_agen_dashboard_overview.png'),
            fullPage: false
        });

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

        console.log('4.1 Login Kurir Agus Driver Gedog...');
        await driverPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        await driverPage.fill('input[name="login"]', 'driver.gedog@myethek.id');
        await driverPage.fill('input[name="password"]', 'password');
        await driverPage.click('button[type="submit"]');
        await driverPage.waitForLoadState('networkidle');

        console.log('4.2 Screenshot Portal Kurir (Pesanan Siap Diambil)...');
        await driverPage.goto(`${BASE_URL}/driver`, { waitUntil: 'networkidle' });
        await driverPage.waitForTimeout(800);
        await driverPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '13_driver_job_available.png'),
            fullPage: false
        });

        console.log('4.3 Kurir mengambil pesanan live...');
        const ambilBtn = await driverPage.$('form[action*="driver"] button:has-text("Ambil Pesanan Ini")');
        if (ambilBtn) {
            await Promise.all([
                driverPage.waitForNavigation({ waitUntil: 'networkidle' }),
                ambilBtn.click()
            ]);
            console.log('✅ Pesanan berhasil diambil kurir');
        }

        console.log('4.4 Screenshot Tugas Pengantaran Aktif...');
        await driverPage.waitForTimeout(800);
        await driverPage.screenshot({
            path: path.join(SCREENSHOTS_DIR, '14_driver_active_delivery.png'),
            fullPage: false
        });

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

        await adminContext.close();
    } else {
        console.log('ℹ️  Mode --render-only aktif: Melewati eksekusi order live, langsung menyusun HTML & PDF responsive...');
    }

    // ==========================================
    // FASE 6: GENERATE EXECUTIVE SUMMARY (HTML & PDF)
    // ==========================================
    console.log('\n--- FASE 6: Generate Executive Summary Document (Responsive HTML & PDF) ---');
    const htmlReportPath = path.join(REPORTS_DIR, 'Executive_Summary_Mak_Ethek.html');
    const pdfReportPath = path.join(REPORTS_DIR, 'Executive_Summary_Mak_Ethek.pdf');

    const htmlContent = generateExecutiveSummaryHtml(liveData);
    fs.writeFileSync(htmlReportPath, htmlContent, 'utf-8');
    console.log(`✅ File HTML berhasil disimpan di: ${htmlReportPath}`);

    console.log('Mengonversi HTML ke PDF publication-ready dengan tata letak A4 presisi...');
    const pdfContext = await browser.newContext({
        viewport: { width: 1200, height: 1600 },
        deviceScaleFactor: 2
    });
    const pdfPage = await pdfContext.newPage();
    await pdfPage.goto(`file:///${htmlReportPath.replace(/\\/g, '/')}`, { waitUntil: 'networkidle' });
    await pdfPage.waitForTimeout(1000);

    await pdfPage.pdf({
        path: pdfReportPath,
        format: 'A4',
        printBackground: true,
        preferCSSPageSize: true,
        displayHeaderFooter: true,
        headerTemplate: `
            <div style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 8px; color: #64748b; width: 100%; padding: 0 10mm; display: flex; justify-content: space-between; border-bottom: 1px solid #fee2e2; padding-bottom: 3px;">
                <span style="font-weight: 700; color: #b91c1c;">MAK ETHEK — Platform Perdagangan & Logistik Hyperlocal Koperasi Jatim</span>
                <span>Dokumen Eksekutif Resmi</span>
            </div>
        `,
        footerTemplate: `
            <div style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 8px; color: #64748b; width: 100%; padding: 0 10mm; display: flex; justify-content: space-between; border-top: 1px solid #fee2e2; padding-top: 3px;">
                <span>Holding Ekosistem Koperasi Jawa Timur</span>
                <span>Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></span>
            </div>
        `,
        margin: {
            top: '15mm',
            bottom: '15mm',
            left: '10mm',
            right: '10mm'
        }
    });
    console.log(`🎉 Berhasil membuat PDF Executive Summary: ${pdfReportPath}`);

    await pdfContext.close();
    await browser.close();

    console.log('\n======================================================');
    console.log('🏆 GENERASI EXECUTIVE SUMMARY SELESAI!');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Executive Summary - Platform Mak Ethek</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #dc2626;
            --primary-dark: #991b1b;
            --primary-light: #ef4444;
            --primary-subtle: #fee2e2;
            --secondary: #0f172a;
            --accent: #d97706;
            --info: #0284c7;
            --bg-light: #fffafa;
            --surface: #ffffff;
            --border: #fecaca;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            line-height: 1.6;
            font-size: 14px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* QUICK NAVIGATION BAR (MOBILE & DESKTOP) */
        .quick-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }

        .nav-container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 14px;
            color: var(--primary-dark);
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 2px;
        }

        .nav-links::-webkit-scrollbar {
            display: none;
        }

        .nav-pill {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 20px;
            background: #f1f5f9;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .nav-pill:hover, .nav-pill:focus {
            background: var(--primary);
            color: white;
        }

        .btn-print {
            background: var(--primary);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.25);
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: var(--primary-dark);
        }

        /* MAIN CONTAINER */
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 25px 20px 60px 20px;
        }

        /* HEADER & HERO COVER */
        .doc-header {
            background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 45%, #dc2626 100%);
            color: white;
            padding: 35px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px -4px rgba(185, 28, 28, 0.3);
            position: relative;
            overflow: hidden;
        }

        .doc-header::after {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }

        .header-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(8px);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .doc-header h1 {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .doc-header p {
            font-size: 13.5px;
            opacity: 0.92;
            max-width: 820px;
            line-height: 1.55;
        }

        .doc-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px 24px;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.22);
            font-size: 12px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-label {
            opacity: 0.8;
        }

        .meta-value {
            font-weight: 600;
        }

        /* METRIC CARDS */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: var(--surface);
            padding: 18px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kpi-title {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 2px;
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
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
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
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        /* DIAGRAM BOX */
        .diagram-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 16px;
            margin: 16px 0;
            border: 1px dashed #cbd5e1;
        }

        .tier-flow {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 8px;
            text-align: center;
            font-size: 11px;
        }

        .tier-node {
            background: white;
            padding: 10px 8px;
            border-radius: 8px;
            flex: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            border-top: 3px solid var(--primary);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .tier-node.accent {
            border-top-color: var(--accent);
        }

        .tier-node.info {
            border-top-color: var(--info);
        }

        .tier-node strong {
            display: block;
            font-size: 11.5px;
            color: var(--secondary);
            margin-bottom: 2px;
        }

        .flow-arrow {
            color: #94a3b8;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* SCREENSHOT GALLERY GRID */
        .screen-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin: 18px 0;
        }

        .screen-single {
            grid-column: span 2;
        }

        .screen-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .screen-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.06);
        }

        .img-wrapper {
            position: relative;
            background: #f1f5f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .img-wrapper::after {
            content: "🔍 Klik untuk Zoom";
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(15, 23, 42, 0.75);
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .img-wrapper:hover::after {
            opacity: 1;
        }

        .screen-card img {
            width: 100%;
            height: auto;
            max-height: 420px;
            object-fit: contain;
            display: block;
            border-bottom: 1px solid var(--border);
        }

        .screen-card.mobile-mock img {
            max-height: 440px;
            object-fit: contain;
            background: #f8fafc;
        }

        .screen-caption {
            padding: 12px 14px;
            background: white;
            flex-grow: 1;
        }

        .screen-caption h4 {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 3px;
        }

        .screen-caption p {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        /* TABLES */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 14px 0;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            background: white;
            min-width: 580px;
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
            font-size: 10px;
            font-weight: 600;
        }

        .badge-success {
            background: #fee2e2;
            color: #b91c1c;
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
            background: #fef2f2;
            border-left: 4px solid var(--primary);
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            margin: 14px 0;
            font-size: 12.5px;
            color: #991b1b;
        }

        .callout-box strong {
            display: block;
            margin-bottom: 3px;
            font-size: 12.5px;
        }

        /* FOOTER */
        .doc-footer {
            text-align: center;
            padding: 25px 20px;
            color: var(--text-muted);
            font-size: 11.5px;
            border-top: 1px solid var(--border);
            margin-top: 25px;
        }

        /* LIGHTBOX MODAL */
        .lightbox-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .lightbox-content {
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .lightbox-content img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            background: white;
            object-fit: contain;
        }

        .lightbox-caption {
            color: white;
            font-size: 13px;
            margin-top: 12px;
            text-align: center;
            font-weight: 500;
        }

        .lightbox-close {
            position: absolute;
            top: -35px;
            right: 0;
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            background: transparent;
            border: none;
        }

        /* ========================================================
           RESPONSIVE RULES: TABLET (<= 992px)
           ======================================================== */
        @media screen and (max-width: 992px) {
            .container {
                padding: 20px 16px;
            }

            .doc-header {
                padding: 28px 24px;
            }

            .doc-header h1 {
                font-size: 22px;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .section-block {
                padding: 22px;
                margin-bottom: 20px;
            }

            .screen-card img,
            .screen-card.mobile-mock img {
                max-height: 380px;
            }
        }

        /* ========================================================
           RESPONSIVE RULES: MOBILE (<= 768px)
           ======================================================== */
        @media screen and (max-width: 768px) {
            body {
                font-size: 13.5px;
            }

            .nav-container {
                padding: 0 12px;
            }

            .nav-brand span {
                display: none;
            }

            .doc-header {
                padding: 22px 18px;
                border-radius: 12px;
            }

            .doc-header h1 {
                font-size: 20px;
            }

            .doc-meta {
                flex-direction: column;
                gap: 8px;
                margin-top: 16px;
                padding-top: 14px;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .tier-flow {
                flex-direction: column;
                gap: 6px;
            }

            .flow-arrow {
                transform: rotate(90deg);
                height: 18px;
            }

            .screen-container {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .screen-single {
                grid-column: span 1;
            }

            .screen-card img,
            .screen-card.mobile-mock img {
                max-height: 340px;
            }

            .section-block {
                padding: 18px 14px;
                border-radius: 10px;
            }

            .section-title {
                font-size: 16px;
            }
        }

        /* ========================================================
           PRINT & PDF OPTIMIZATION (A4 PUBLICATION STANDARD)
           ======================================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 14mm 10mm 14mm 10mm;
            }

            body {
                background: white !important;
                color: #000 !important;
                font-size: 11.5px !important;
                line-height: 1.45 !important;
            }

            .quick-nav, .btn-print, .lightbox-modal, .img-wrapper::after {
                display: none !important;
            }

            .container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .doc-header {
                box-shadow: none !important;
                border: 1px solid #b91c1c !important;
                padding: 20px 22px !important;
                margin-bottom: 16px !important;
                border-radius: 10px !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .doc-header h1 {
                font-size: 20px !important;
                margin-bottom: 6px !important;
            }

            .doc-header p {
                font-size: 11px !important;
                line-height: 1.45 !important;
            }

            .doc-meta {
                margin-top: 12px !important;
                padding-top: 10px !important;
                font-size: 10px !important;
                gap: 8px 18px !important;
            }

            .kpi-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 10px !important;
                margin-bottom: 16px !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .kpi-card {
                padding: 12px !important;
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }

            .kpi-title {
                font-size: 9px !important;
            }

            .kpi-value {
                font-size: 15px !important;
            }

            .kpi-sub {
                font-size: 9.5px !important;
            }

            .section-block {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                padding: 16px 18px !important;
                margin-bottom: 14px !important;
                border-radius: 8px !important;
                page-break-inside: auto;
                break-inside: auto;
            }

            .section-header {
                margin-bottom: 12px !important;
                padding-bottom: 8px !important;
                page-break-after: avoid;
                break-after: avoid;
            }

            .section-num {
                width: 24px !important;
                height: 24px !important;
                font-size: 11px !important;
                border-radius: 6px !important;
            }

            .section-title {
                font-size: 14.5px !important;
            }

            .section-desc {
                font-size: 10.5px !important;
            }

            .diagram-box {
                padding: 10px !important;
                margin: 10px 0 !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .tier-flow {
                gap: 4px !important;
            }

            .tier-node {
                padding: 6px 4px !important;
                border-top-width: 2px !important;
            }

            .tier-node strong {
                font-size: 9.5px !important;
            }

            .flow-arrow {
                font-size: 11px !important;
            }

            .callout-box {
                padding: 8px 12px !important;
                margin: 10px 0 !important;
                font-size: 10.5px !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* SCREENSHOT PRINT CONTROLS */
            .screen-container {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
                margin: 10px 0 !important;
            }

            .screen-card {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin-bottom: 0 !important;
            }

            .screen-card img {
                max-height: 210px !important;
                object-fit: contain !important;
                background: #ffffff !important;
            }

            .screen-card.mobile-mock img {
                max-height: 235px !important;
                object-fit: contain !important;
                background: #ffffff !important;
            }

            .screen-caption {
                padding: 8px 10px !important;
            }

            .screen-caption h4 {
                font-size: 11px !important;
                margin-bottom: 2px !important;
            }

            .screen-caption p {
                font-size: 9.5px !important;
                line-height: 1.35 !important;
            }

            /* TABLES PRINT */
            .table-responsive {
                overflow: visible !important;
                border: 1px solid #cbd5e1 !important;
                margin: 10px 0 !important;
            }

            .data-table {
                min-width: 100% !important;
                font-size: 10px !important;
            }

            .data-table th {
                padding: 6px 8px !important;
                font-size: 9px !important;
                background: #f1f5f9 !important;
            }

            .data-table td {
                padding: 6px 8px !important;
            }

            .data-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* EXPLICIT PAGE BREAKS */
            .page-break {
                page-break-before: always !important;
                break-before: page !important;
                padding-top: 10px !important;
            }

            .doc-footer {
                padding: 16px 10px !important;
                margin-top: 16px !important;
                font-size: 9.5px !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<!-- QUICK NAVIGATION (WEB & TABLET) -->
<nav class="quick-nav">
    <div class="nav-container">
        <a href="#cover" class="nav-brand">
            🛒 <span>Mak Ethek</span>
        </a>
        <div class="nav-links">
            <a href="#cover" class="nav-pill">Cover</a>
            <a href="#bab1" class="nav-pill">1. Arsitektur</a>
            <a href="#bab2" class="nav-pill">2. Belanja Warga</a>
            <a href="#bab3" class="nav-pill">3. Penjualan Toko</a>
            <a href="#bab4" class="nav-pill">4. Kulakan B2B</a>
            <a href="#bab5" class="nav-pill">5. Kurir & Escrow</a>
            <a href="#bab6" class="nav-pill">6. Kesimpulan</a>
        </div>
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak / PDF
        </button>
    </div>
</nav>

<div class="container">
    <!-- HEADER COVER -->
    <header class="doc-header" id="cover">
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
                <span class="meta-label">Metode Uji:</span>
                <span class="meta-value">Automated Playwright Real-Flow Testing</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Wilayah Uji:</span>
                <span class="meta-value">Kel. Gedog & Kec. Sananwetan, Kota Blitar</span>
            </div>
        </div>
    </header>

    <!-- KEY METRIC HIGHLIGHTS -->
    <section class="kpi-grid">
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
    </section>

    <!-- BAB 1: ARSITEKTUR EKOSISTEM -->
    <section class="section-block" id="bab1">
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
    </section>

    <!-- BAB 2: LEVEL CUSTOMER / USER -->
    <section class="section-block page-break" id="bab2">
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
                <div class="img-wrapper" onclick="openLightbox('screenshots/01_storefront_katalog.png', 'Etalase & Deteksi Lokasi Warga')">
                    <img src="screenshots/01_storefront_katalog.png" alt="Storefront Mak Ethek" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>1. Etalase & Deteksi Lokasi Warga</h4>
                    <p>Deteksi wilayah aktif di Kelurahan Gedog dan pemilihan otomatis warung mitra terdekat untuk menjamin kepastian pasokan.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/02_detail_produk.png', 'Detail Produk Satuan Tradisional')">
                    <img src="screenshots/02_detail_produk.png" alt="Detail Produk" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>2. Detail Produk Satuan Tradisional</h4>
                    <p>Informasi harga terstandarisasi, satuan lokal (sak 5kg, kg, liter), deskripsi lumbung petani, dan stok riil toko mitra.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/03_keranjang_belanja.png', 'Keranjang Belanja (Single-Merchant Policy)')">
                    <img src="screenshots/03_keranjang_belanja.png" alt="Keranjang Belanja" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>3. Keranjang Belanja (Single-Merchant Policy)</h4>
                    <p>Enforcement 1 keranjang 1 toko demi menghindari pemisahan ongkir yang merugikan warga dan pedagang mikro.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/04_checkout_pengiriman.png', 'Checkout Logistik & Rincian Transparan')">
                    <img src="screenshots/04_checkout_pengiriman.png" alt="Checkout Pengiriman" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>4. Checkout Logistik & Rincian Transparan</h4>
                    <p>Perhitungan jarak darat rute Haversine × 1.3, pilihan kurir ojol lokal / antar warung, dan platform fee koperasi Rp 1.000.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/05_customer_order_success.png', 'Milestone Tracking & Kode OTP Pelanggan')">
                    <img src="screenshots/05_customer_order_success.png" alt="Order Success" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>5. Milestone Tracking & Kode OTP Pelanggan</h4>
                    <p>Pesanan <strong>${data.orderNumber}</strong> berhasil dibuat. Menampilkan 4-digit OTP (<span class="badge-code">${data.orderOtp}</span>) dan tombol WhatsApp konfirmasi ke warung.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/06_customer_portal_orders.png', 'Portal Riwayat Pesanan Warga')">
                    <img src="screenshots/06_customer_portal_orders.png" alt="Portal Riwayat Pesanan" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>6. Portal Riwayat Pesanan Warga</h4>
                    <p>Pelanggan dapat memantau status pesanan kapan pun secara real-time minim kuota data.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- BAB 3: LEVEL SUB-AGEN & AGEN -->
    <section class="section-block page-break" id="bab3">
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
                <div class="img-wrapper" onclick="openLightbox('screenshots/08_subagen_orders_incoming.png', 'Notifikasi Pesanan Masuk di Warung Sub-Agen')">
                    <img src="screenshots/08_subagen_orders_incoming.png" alt="Pesanan Masuk Sub-Agen" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>7. Notifikasi Pesanan Masuk di Warung Sub-Agen</h4>
                    <p>Warung Mak Ethek Gedog menerima pesanan retail atas nama Siti Khadijah. Tombol <em>"Terima & Kemas"</em> siap dieksekusi.</p>
                </div>
            </div>
            <div class="screen-card">
                <div class="img-wrapper" onclick="openLightbox('screenshots/09_subagen_order_processing.png', 'Status Pesanan Berpindah ke Pengemasan')">
                    <img src="screenshots/09_subagen_order_processing.png" alt="Pesanan Sedang Dikemas" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>8. Status Pesanan Berpindah ke Pengemasan</h4>
                    <p>Setelah dikonfirmasi, status pesanan beralih ke <em>Processing</em> dan paket siap diambil oleh kurir ojol lokal.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card">
                <div class="img-wrapper" onclick="openLightbox('screenshots/11_agen_dashboard_overview.png', 'Dashboard Agen Kecamatan Sananwetan')">
                    <img src="screenshots/11_agen_dashboard_overview.png" alt="Dashboard Agen Sananwetan" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>9. Dashboard Agen Kecamatan Sananwetan</h4>
                    <p>Monitoring omset kecamatan, mengelola puluhan sub-agen desa, dan siap melayani pesanan fallback bila warung desa tutup.</p>
                </div>
            </div>
            <div class="screen-card">
                <div class="img-wrapper" onclick="openLightbox('screenshots/12_agen_inventory_buffer.png', 'Buffer Stock & Manajemen Inventori Agen')">
                    <img src="screenshots/12_agen_inventory_buffer.png" alt="Inventori Agen" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>10. Buffer Stock & Manajemen Inventori Agen</h4>
                    <p>Stok pengaman di level kecamatan untuk memasok kebutuhan kulakan warung desa dan cadangan pemenuhan langsung.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- BAB 4: KULAKAN B2B (PROCUREMENT) -->
    <section class="section-block page-break" id="bab4">
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
                <div class="img-wrapper" onclick="openLightbox('screenshots/10_b2b_kulakan_procurement.png', 'Portal Kulakan B2B Terintegrasi (Sub-Agen ➔ Agen Kecamatan)')">
                    <img src="screenshots/10_b2b_kulakan_procurement.png" alt="Katalog Kulakan B2B" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>11. Portal Kulakan B2B Terintegrasi (Sub-Agen ➔ Agen Kecamatan)</h4>
                    <p>Tampilan katalog grosir khusus mitra dengan Tier Pricing resmi (misal Beras Lahap Rp 68.500 dengan MOQ 5 sak, Minyakita Rp 14.200 dengan MOQ 12 pcs). Penerimaan barang otomatis meng-update stok etalase eceran warung desa.</p>
                </div>
            </div>
        </div>

        <div class="table-responsive">
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
    </section>

    <!-- BAB 5: KURIR LOGISTIK & SETTLEMENT ESCROW -->
    <section class="section-block page-break" id="bab5">
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
                <div class="img-wrapper" onclick="openLightbox('screenshots/13_driver_job_available.png', 'Pesanan Masuk di Portal Kurir Lokal')">
                    <img src="screenshots/13_driver_job_available.png" alt="Driver Job Available" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>12. Pesanan Masuk di Portal Kurir Lokal</h4>
                    <p>Kurir Agus melihat pesanan siap ambil di Warung Gedog lengkap dengan nominal ongkir dan rute tujuan.</p>
                </div>
            </div>
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/14_driver_active_delivery.png', 'Pengantaran Aktif & Form Validasi OTP')">
                    <img src="screenshots/14_driver_active_delivery.png" alt="Driver Active Delivery" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>13. Pengantaran Aktif & Form Validasi OTP</h4>
                    <p>Kurir menuju rumah Siti Khadijah di Jl. Kawi. Kurir meminta kode OTP 4-digit untuk menyelesaikan pengantaran.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card mobile-mock">
                <div class="img-wrapper" onclick="openLightbox('screenshots/15_driver_job_completed.png', 'Verifikasi Berhasil & Ongkir Masuk Dompet')">
                    <img src="screenshots/15_driver_job_completed.png" alt="Driver Job Completed" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>14. Verifikasi Berhasil & Ongkir Masuk Dompet</h4>
                    <p>Begitu OTP cocok, status berubah menjadi <em>Delivered</em> dan ongkir Rp 6.000 seketika masuk ke dompet kurir tanpa potongan.</p>
                </div>
            </div>
            <div class="screen-card">
                <div class="img-wrapper" onclick="openLightbox('screenshots/16_subagen_finance_settlement.png', 'Dompet Toko & Double-Entry Ledger Koperasi')">
                    <img src="screenshots/16_subagen_finance_settlement.png" alt="Settlement Keuangan Sub-Agen" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>15. Dompet Toko & Double-Entry Ledger Koperasi</h4>
                    <p>Hasil penjualan pokok produk langsung dicairkan dari <em>escrow_pending</em> ke <em>balance_available</em> toko mitra.</p>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen-card screen-single">
                <div class="img-wrapper" onclick="openLightbox('screenshots/17_admin_holding_overview.png', 'Panel Audit & Konsolidasi Holding Koperasi Jawa Timur')">
                    <img src="screenshots/17_admin_holding_overview.png" alt="Admin Holding Overview" loading="lazy">
                </div>
                <div class="screen-caption">
                    <h4>16. Panel Audit & Konsolidasi Holding Koperasi Jawa Timur</h4>
                    <p>Pengawasan menyeluruh terhadap perputaran arus barang, fee platform koperasi, dan kepatuhan distribusi di seluruh kabupaten/kota.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- BAB 6: KESIMPULAN & REKOMENDASI -->
    <section class="section-block page-break" id="bab6">
        <div class="section-header">
            <div class="section-num">6</div>
            <div>
                <h2 class="section-title">Kesimpulan Strategis & Nilai Tambah Platform</h2>
                <div class="section-desc">Dampak ekonomi terhadap warung tradisional dan stabilitas harga sembako</div>
            </div>
        </div>

        <div class="table-responsive">
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
    </section>

    <!-- FOOTER -->
    <footer class="doc-footer">
        <p><strong>Mak Ethek (MyEthek) — Platform Perdagangan & Logistik Hyperlocal Koperasi Jawa Timur</strong></p>
        <p>Dokumen ini di-generate secara otomatis menggunakan Playwright Automation Engine pada ${todayStr}.</p>
    </footer>
</div>

<!-- LIGHTBOX MODAL -->
<div class="lightbox-modal" id="lightboxModal" onclick="closeLightbox()">
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img id="lightboxImg" src="" alt="Preview Gambar">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>
</div>

<script>
    function openLightbox(src, caption) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightboxCaption').innerText = caption;
        document.getElementById('lightboxModal').style.display = 'flex';
    }

    function closeLightbox() {
        document.getElementById('lightboxModal').style.display = 'none';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
</script>

</body>
</html>`;
}

run().catch(err => {
    console.error('❌ Terjadi kesalahan pada eksekusi:', err);
    process.exit(1);
});
