<script setup>
    import { Link } from '@inertiajs/vue3'
    import AppLayout from '@/layouts/AppLayout.vue'
    import { usePage } from '@inertiajs/vue3';

    const { auth } = usePage().props;
</script>

<template>
    <AppLayout>
        <main>
            <section class="hero">
                <h2>Veido savu fitnesa nākotni jau šodien</h2>
                <p class="hero-subtitle">Pārveido savus treniņus</p>
                <Link href="/login" class="nav-link"><button class="cta-button">Sāc tagad - tas ir bez maksas</button></Link>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Lietotāju apmierinātība</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">2.5x</span>
                        <span class="stat-label">Ātrāka progresija</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">30 dienas</span>
                        <span class="stat-label">Līdz redzamām izmaiņām</span>
                    </div>
                </div>
            </section>

            <section class="features">
                <h3>Kāpēc izvēlēties FitForge?</h3>
                <p class="section-intro">Mēs apvienojam modernāko tehnoloģiju ar pierādītu fitnesa zinātni, lai nodrošinātu rezultātus</p>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>Tevi, bet stiprāku 💪</h4>
                        <p>Mūsu adaptīvie algoritmi veido personalizētus plānus, kas attīstās līdz ar jūsu progresu, nodrošinot nepārtrauktu izaicinājumu un izaugsmi.</p>
                    </div>
                    <div class="feature-card">
                        <h4>Vienmēr jūsu pusē 👟</h4>
                        <p>No iesācēja modifikācijām līdz profesionālām tehnikām - mēs sniedzam norādījumus katram fitnesa līmenim un katrai treniņa dienai.</p>
                    </div>
                    <div class="feature-card">
                        <h4>Veidots reālai dzīvei 🌍</h4>
                        <p>Ātri 15 minūšu treniņi, iespējas bez aprīkojuma un ceļojumiem draudzīgas rutīnas, kas iederas jūsu aizņemtajā grafikā.</p>
                    </div>
                </div>
            </section>

            <section class="testimonials">
                <h3>Veiksma stāsti</h3>
                <div class="testimonial-grid">
                    <div class="testimonial-card">
                        <p class="quote">"Zaudēju 11 kg 3 mēnešos ar rutīnām, kas faktiski iederējās manā darba grafikā!"</p>
                        <p class="author">- Sāra K., skolotāja</p>
                    </div>
                    <div class="testimonial-card">
                        <p class="quote">"Beidzot atradu programmu, kas pielāgojās manam ceļa savainojumam, vienlaikus palīdzot man attīstīties"</p>
                        <p class="author">- Maikls T., veterāns</p>
                    </div>
                </div>
            </section>

            <section class="cta-section">
                <h3>Gatavs pārveidot savu fitnesa ceļojumu?</h3>
                <p>Pievienojieties mūsu kopienai jau šodien!</p>
                <Link href="/register" class="nav-link"><button class="cta-button secondary">Sākt tagad</button></Link>
            </section>
        </main>
    </AppLayout>
</template>

<style>
    @media (min-width: 1024px) {
        .main {
            min-height: 100vh;
        }

        img {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 40%;
        }
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .hero {
        text-align: center;
        padding: 120px 10% 80px;
        background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url("home_back.webp");
        background-size: cover;
        background-position: center;
        color: white;
    }

        .hero h2 {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

    .hero-subtitle {
        font-size: 1.3rem;
        max-width: 700px;
        margin: 0 auto 2rem;
        opacity: 0.9;
    }

    .hero-stats {
        display: flex;
        justify-content: center;
        gap: 3rem;
        margin-top: 3rem;
        flex-wrap: wrap;
    }

    .stat-item {
        display: flex;
        flex-direction: column;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #ff5733;
    }

    .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .cta-button {
        margin-top: 20px;
        padding: 14px 32px;
        font-size: 1.1rem;
        color: white;
        background: #ff5733;
        border: none;
        cursor: pointer;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
    }

        .cta-button:hover {
            background: #e64a19;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230, 74, 25, 0.3);
        }

        .cta-button.secondary {
            background: transparent;
            border: 2px solid white;
            margin-top: 1rem;
        }

            .cta-button.secondary:hover {
                background: rgba(255,255,255,0.1);
            }

    .features {
        text-align: center;
        padding: 80px 10%;
        background: white;
    }

    .section-intro {
        max-width: 700px;
        margin: 0 auto 3rem;
        color: #555;
        font-size: 1.1rem;
    }

    .feature-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .feature-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        width: 100%;
        max-width: 350px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        text-align: left;
    }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-card h4 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .feature-card p {
            color: #666;
            font-size: 1rem;
        }

    .testimonials {
        padding: 80px 10%;
        background: #f9f9f9;
        text-align: center;
    }

    .testimonial-grid {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
        margin-top: 3rem;
    }

    .testimonial-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .quote {
        font-style: italic;
        font-size: 1.1rem;
        color: #444;
        margin-bottom: 1rem;
    }

    .author {
        font-weight: bold;
        color: #ff5733;
    }

    .cta-section {
        text-align: center;
        padding: 80px 10%;
        background: linear-gradient(135deg, #ff5733 0%, #e64a19 100%);
        color: white;
    }

        .cta-section h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            max-width: 600px;
            margin: 0 auto 2rem;
            opacity: 0.9;
            font-size: 1.1rem;
        }

    @media (max-width: 768px) {
        .hero h2 {
            font-size: 2rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
        }

        .feature-grid,
        .testimonial-grid {
            flex-direction: column;
            align-items: center;
        }

        .hero-stats {
            gap: 2rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }
    }
</style>
