<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Hero Section -->
<div class="container-fluid bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Enhance Your Text with AI</h1>
                <p class="lead mb-4">Transform your content with our powerful AI-driven text enhancement tools. Perfect for content creators, marketers, and businesses.</p>
                <a href="<?= site_url('auth/register') ?>" class="btn btn-light btn-lg">Start Free Trial</a>
            </div>
            <div class="col-lg-6">
                <img src="<?= base_url('assets/images/hero.svg') ?>" alt="AI Text Enhancement" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container py-5">
    <h2 class="text-center mb-5">Why Choose AI Text Enhancer Pro?</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-magic fa-3x text-primary mb-3"></i>
                    <h3 class="h4 mb-3">Smart Enhancement</h3>
                    <p class="text-muted">Our AI understands context and enhances your text while maintaining its original meaning.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-bolt fa-3x text-primary mb-3"></i>
                    <h3 class="h4 mb-3">Lightning Fast</h3>
                    <p class="text-muted">Get instant results with our high-performance API, perfect for real-time applications.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                    <h3 class="h4 mb-3">Secure & Private</h3>
                    <p class="text-muted">Your data is encrypted and protected. We never store or share your content.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="container py-5">
    <h2 class="text-center mb-5">Choose Your Plan</h2>
    <div class="row g-4 justify-content-center">
        <!-- Small Plan -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="h4 mb-3">Small</h3>
                    <div class="display-4 mb-3">$29<small class="fs-6">/mo</small></div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>1,000 requests/month</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>2 users</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic support</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>API access</li>
                    </ul>
                    <a href="<?= site_url('auth/register?plan=small') ?>" class="btn btn-outline-primary">Start with Small</a>
                </div>
            </div>
        </div>
        <!-- Medium Plan -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-lg bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="h4 mb-3">Medium</h3>
                    <div class="display-4 mb-3">$79<small class="fs-6">/mo</small></div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check me-2"></i>5,000 requests/month</li>
                        <li class="mb-2"><i class="fas fa-check me-2"></i>5 users</li>
                        <li class="mb-2"><i class="fas fa-check me-2"></i>Priority support</li>
                        <li class="mb-2"><i class="fas fa-check me-2"></i>API access</li>
                        <li class="mb-2"><i class="fas fa-check me-2"></i>Advanced analytics</li>
                    </ul>
                    <a href="<?= site_url('auth/register?plan=medium') ?>" class="btn btn-light">Start with Medium</a>
                </div>
            </div>
        </div>
        <!-- Large Plan -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="h4 mb-3">Large</h3>
                    <div class="display-4 mb-3">$199<small class="fs-6">/mo</small></div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>15,000 requests/month</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unlimited users</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>24/7 support</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>API access</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Custom integrations</li>
                    </ul>
                    <a href="<?= site_url('auth/register?plan=large') ?>" class="btn btn-outline-primary">Start with Large</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="container-fluid bg-light py-5">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Transform Your Content?</h2>
        <p class="lead mb-4">Start your 14-day free trial today. No credit card required.</p>
        <a href="<?= site_url('auth/register') ?>" class="btn btn-primary btn-lg">Get Started Now</a>
    </div>
</div>

<?= $this->endSection() ?>
