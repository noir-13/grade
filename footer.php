<!-- Footer -->
<footer style="background-color: white; border-top: 1px solid rgba(0,0,0,0.05); padding: 40px 0; margin-top: auto;">
    <div class="vds-container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-3 mb-md-0 text-center text-md-start">
                <p style="color: var(--vds-text-muted); margin: 0; font-size: 0.9rem;">
                    &copy; <?php echo date('Y'); ?> Kolehiyo ng Lungsod ng Dasmariñas. All rights reserved.
                </p>
            </div>
            <div class="d-flex gap-4">
                <a href="#" onclick="openModal('privacyModal'); return false;" style="color: var(--vds-text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;">Privacy Policy</a>
                <a href="#" onclick="openModal('termsModal'); return false;" style="color: var(--vds-text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;">Terms of Service</a>
                <a href="index.php#contact" style="color: var(--vds-text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;">Support</a>
            </div>
        </div>
    </div>
</footer>

<!-- Privacy Modal -->
<div id="privacyModal" class="vds-modal-overlay">
    <div class="vds-modal">
        <div class="vds-modal-header">
            <h3 class="vds-h3 mb-0">Privacy Policy</h3>
            <button class="vds-modal-close" onclick="closeModal('privacyModal')">&times;</button>
        </div>
        <div class="vds-modal-body">
            <p class="vds-text-lead mb-4">Last updated: November 2025</p>
            
            <h4 class="vds-h4 fw-bold mb-2">1. Information We Collect</h4>
            <p class="vds-text-muted mb-4">
                We collect information that you provide directly to us, such as when you create an account, update your profile, or communicate with us. This includes:
                <ul class="mt-2 ps-3" style="list-style-type: disc;">
                    <li>Personal identification (Name, Student ID, Email)</li>
                    <li>Academic records and grades</li>
                    <li>Login credentials</li>
                </ul>
            </p>

            <h4 class="vds-h4 fw-bold mb-2">2. How We Use Your Information</h4>
            <p class="vds-text-muted mb-4">
                We use the information we collect to:
                <ul class="mt-2 ps-3" style="list-style-type: disc;">
                    <li>Provide, maintain, and improve the KLD Grade System</li>
                    <li>Process and display your academic grades</li>
                    <li>Send you technical notices and support messages</li>
                </ul>
            </p>

            <h4 class="vds-h4 fw-bold mb-2">3. Data Security</h4>
            <p class="vds-text-muted mb-4">
                We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.
            </p>

            <h4 class="vds-h4 fw-bold mb-2">4. Contact Us</h4>
            <p class="vds-text-muted mb-0">
                If you have any questions about this Privacy Policy, please contact us at <a href="mailto:privacy@kld.edu.ph" style="color: var(--vds-forest); font-weight: 600;">privacy@kld.edu.ph</a>.
            </p>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div id="termsModal" class="vds-modal-overlay">
    <div class="vds-modal">
        <div class="vds-modal-header">
            <h3 class="vds-h3 mb-0">Terms of Service</h3>
            <button class="vds-modal-close" onclick="closeModal('termsModal')">&times;</button>
        </div>
        <div class="vds-modal-body">
            <p class="vds-text-lead mb-4">Last updated: November 2025</p>

            <h4 class="vds-h4 fw-bold mb-2">1. Acceptance of Terms</h4>
            <p class="vds-text-muted mb-4">
                By accessing and using the KLD Grade System, you accept and agree to be bound by the terms and provision of this agreement.
            </p>

            <h4 class="vds-h4 fw-bold mb-2">2. User Account</h4>
            <p class="vds-text-muted mb-4">
                You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer. You agree to accept responsibility for all activities that occur under your account or password.
            </p>

            <h4 class="vds-h4 fw-bold mb-2">3. Acceptable Use</h4>
            <p class="vds-text-muted mb-4">
                You agree not to use the system for any unlawful purpose or any purpose prohibited under this clause. You agree not to use the system in any way that could damage the system or general business of KLD.
            </p>

            <h4 class="vds-h4 fw-bold mb-2">4. Limitation of Liability</h4>
            <p class="vds-text-muted mb-4">
                In no event shall KLD, nor any of its officers, directors and employees, be held liable for anything arising out of or in any way connected with your use of this website.
            </p>

            <h4 class="vds-h4 fw-bold mb-2">5. Changes to Terms</h4>
            <p class="vds-text-muted mb-0">
                KLD reserves the right to modify these terms at any time. We do so by posting and drawing attention to the updated terms on the Site. Your decision to continue to visit and make use of the Site after such changes have been made constitutes your formal acceptance of the new Terms of Service.
            </p>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
// Close on click outside
document.querySelectorAll('.vds-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
</script>
