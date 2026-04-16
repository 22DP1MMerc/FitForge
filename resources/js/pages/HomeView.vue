<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3';

const { auth } = usePage().props;
</script>

<template>
    <AppLayout>
        <main>
            <!-- Hero Section -->
            <section class="hero">
                <div class="hero-content">
                    <div class="logo-badge">
                        <span class="logo-icon">🏋️</span>
                        <span class="logo-text">FitForge</span>
                    </div>
                    <h1>Treniņu un fiziskās aktivitātes <span class="highlight">uzskaites sistēma</span></h1>
                    <p class="hero-subtitle">
                        Personalizēti treniņu plāni, progresa izsekošana un personīgo rekordu uzskaite
                    </p>
                    <div class="hero-buttons">
                        <Link href="/register" class="btn btn-primary">Izveidot profilu</Link>
                        <Link href="/exercises" class="btn btn-outline">Vingrinājumu katalogs</Link>
                    </div>

                    <!-- Funkciju pārskats -->
                    <div class="feature-highlights">
                        <div class="highlight-item">
                            <span>✓</span>
                            <span>Treniņu rutīnu veidošana</span>
                        </div>
                        <div class="highlight-item">
                            <span>✓</span>
                            <span>Sesiju reģistrēšana</span>
                        </div>
                        <div class="highlight-item">
                            <span>✓</span>
                            <span>Personīgie rekordi</span>
                        </div>
                        <div class="highlight-item">
                            <span>✓</span>
                            <span>Mērķu izvirzīšana</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Galvenās funkcijas (atbilstoši 2.2. funkcionālajām prasībām) -->
            <section class="functions">
                <h2>Sistēmas funkcionalitāte</h2>
                <p class="section-intro">FitForge nodrošina pilnu treniņu procesa ciklu - no plānošanas līdz rezultātu analīzei</p>

                <div class="functions-grid">

                    <!-- Treniņu plānošana -->
                    <div class="function-group">
                        <div class="group-header">
                            <div class="group-icon">📋</div>
                            <h3>Treniņu plānošana</h3>
                        </div>
                        <ul class="function-list">
                            <li>Jaunu rutīnu izveide</li>
                            <li>Vingrinājumu pievienošana rutīnai</li>
                            <li>Pieeju un atkārtojumu plānošana</li>
                            <li>Rutīnu publiskošana</li>
                            <li>Vingrinājumu kataloga pārlūkošana</li>
                        </ul>
                    </div>

                    <!-- Treniņu izpilde -->
                    <div class="function-group">
                        <div class="group-header">
                            <div class="group-icon">🏃</div>
                            <h3>Treniņu izpilde</h3>
                        </div>
                        <ul class="function-list">
                            <li>Treniņu sesiju reģistrēšana</li>
                            <li>Faktisko rezultātu fiksēšana</li>
                            <li>Izmantotā svara uzskaite</li>
                            <li>Automātiska rekordu atpazīšana</li>
                            <li>Treniņu ilguma aprēķins</li>
                        </ul>
                    </div>

                    <!-- Progresa uzskaite -->
                    <div class="function-group">
                        <div class="group-header">
                            <div class="group-icon">📊</div>
                            <h3>Progresa uzskaite</h3>
                        </div>
                        <ul class="function-list">
                            <li>Treniņu vēstures apskate</li>
                            <li>Personīgo rekordu saraksts</li>
                            <li>Mērķu izvirzīšana</li>
                            <li>Kopsavilkuma statistika</li>
                            <li>Progresa vizualizācija</li>
                        </ul>
                    </div>
                </div>
            </section>


            <!-- Reģistrācijas aicinājums -->
            <section class="cta-section">
                <h3>Sāc dokumentēt savus treniņus jau šodien</h3>
                <p>Reģistrējies bez maksas un iegūsti pilnu kontroli pār savu fitnesa progresu</p>
                <div class="cta-buttons">
                    <Link href="/register" class="btn btn-primary btn-large">Izveidot profilu</Link>
                    <Link href="/login" class="btn btn-outline-light">Pieteikties</Link>
                </div>
                <p class="small-note">Bezmaksas profils ietver: treniņu plānu veidošanu, sesiju reģistrēšanu un personīgos rekordus</p>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    main {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        line-height: 1.5;
        color: #1a1a1a;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        padding: 60px 10% 80px;
    }

    .hero-content {
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
    }

    .logo-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.1);
        padding: 8px 20px;
        border-radius: 40px;
        margin-bottom: 2rem;
    }

    .logo-icon {
        font-size: 1.25rem;
    }

    .logo-text {
        font-weight: 600;
        letter-spacing: 1px;
    }

    .hero h1 {
        font-size: 2.8rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1.5rem;
    }

    .highlight {
        color: #f97316;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        opacity: 0.9;
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 3rem;
    }

    .feature-highlights {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
        padding-top: 2rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .highlight-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }

        .highlight-item span:first-child {
            color: #22c55e;
            font-weight: bold;
        }

    /* Buttons */
    .btn {
        display: inline-block;
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        font-size: 1rem;
    }

    .btn-primary {
        background: #f97316;
        color: white;
        border: none;
    }

        .btn-primary:hover {
            background: #ea580c;
            transform: translateY(-1px);
        }

    .btn-outline {
        background: transparent;
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
    }

        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.5);
        }

    .btn-outline-light {
        background: transparent;
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
    }

        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
        }

    .btn-secondary {
        background: #f1f5f9;
        color: #1e293b;
    }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

    .btn-large {
        padding: 14px 36px;
        font-size: 1.125rem;
    }

    /* Functions Section */
    .functions {
        padding: 80px 10%;
        background: white;
        text-align: center;
    }

        .functions h2 {
            font-size: 2.25rem;
            margin-bottom: 1rem;
        }

    .section-intro {
        font-size: 1.125rem;
        color: #64748b;
        max-width: 700px;
        margin: 0 auto 3rem;
    }

    .functions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .function-group {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: left;
        transition: all 0.2s ease;
    }

        .function-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

    .group-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f97316;
    }

    .group-icon {
        font-size: 1.75rem;
    }

    .group-header h3 {
        font-size: 1.25rem;
        color: #0f172a;
    }

    .function-list {
        list-style: none;
    }

        .function-list li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            color: #334155;
            font-size: 0.95rem;
        }

            .function-list li::before {
                content: "▹";
                position: absolute;
                left: 0;
                color: #f97316;
            }

    /* Exercise Preview */
    .exercise-preview {
        background: #f1f5f9;
        padding: 80px 10%;
        text-align: center;
    }

    .exercise-filters {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }

    .filter-select, .search-input {
        padding: 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.9rem;
        background: white;
    }

    .search-input {
        min-width: 250px;
    }

    .exercise-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.5rem;
        max-width: 1000px;
        margin: 0 auto 2rem;
    }

    .exercise-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: left;
        transition: all 0.2s ease;
    }

        .exercise-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .exercise-card h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

    .muscle-group {
        font-size: 0.8rem;
        color: #f97316;
        margin-bottom: 0.5rem;
    }

    .exercise-desc {
        font-size: 0.85rem;
        color: #64748b;
    }

    .text-center {
        text-align: center;
    }

    /* Architecture Section */
    .architecture {
        padding: 80px 10%;
        background: white;
        text-align: center;
    }

        .architecture h2 {
            font-size: 2.25rem;
            margin-bottom: 1rem;
        }

    .arch-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 2rem;
        max-width: 1100px;
        margin: 0 auto;
    }

    .arch-card {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 12px;
        position: relative;
        text-align: left;
    }

    .arch-number {
        width: 40px;
        height: 40px;
        background: #f97316;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .arch-card h3 {
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
        color: #0f172a;
    }

    .arch-card p {
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.5;
    }

    /* Database Section */
    .database {
        background: #f1f5f9;
        padding: 80px 10%;
        text-align: center;
    }

        .database h2 {
            font-size: 2.25rem;
            margin-bottom: 1rem;
        }

    .db-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    .db-table {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        text-align: left;
        border-left: 3px solid #f97316;
    }

        .db-table h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: #0f172a;
        }

        .db-table p {
            font-size: 0.8rem;
            color: #64748b;
        }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        text-align: center;
        padding: 80px 10%;
    }

        .cta-section h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            margin-bottom: 2rem;
            opacity: 0.9;
        }

    .cta-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }

    .small-note {
        font-size: 0.8rem;
        opacity: 0.7;
        margin-bottom: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 1.8rem;
        }

        .hero-subtitle {
            font-size: 1rem;
        }

        .functions h2,
        .architecture h2,
        .database h2,
        .exercise-preview h2 {
            font-size: 1.75rem;
        }

        .functions-grid,
        .arch-grid,
        .exercise-grid {
            grid-template-columns: 1fr;
        }

        .feature-highlights {
            gap: 1rem;
        }

        .highlight-item {
            font-size: 0.8rem;
        }

        .exercise-filters {
            flex-direction: column;
            align-items: stretch;
        }

        .search-input {
            width: 100%;
        }

        .btn {
            padding: 10px 20px;
        }
    }

    @media (max-width: 480px) {
        .hero {
            padding: 40px 5% 60px;
        }

        .hero-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            text-align: center;
        }

        .db-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
