<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config.php';

?>


<?php require_once $gc['path']['root'] . '/inc/header.php'; ?>


<div class="container mx-auto p-16 max-w-4xl">
    <h1 class="text-3xl font-bold mb-6">Privacy Policy</h1>
    <p class="mb-4">Last updated: [11 January 2023]</p>
    <!-- Introduction -->
    <h2 class="text-2xl font-semibold my-4">Introduction</h2>
    <p class="mb-4">
        Welcome to ThePythagoras.com (the "Site"). At ThePythagoras, we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, and disclose your personal data when you use our services.
    </p>
    <!-- Information Collection -->
    <h2 class="text-2xl font-semibold my-4">Information Collection</h2>
    <p class="mb-4">
        We collect various types of information when you visit and interact with our Site. This may include personal information such as your name, email address, and any other information you may provide when contacting us.
    </p>
    <!-- Use of Information -->
    <h2 class="text-2xl font-semibold my-4">Use of Information</h2>
    <p class="mb-4">
        We may use the collected information for various purposes, including but not limited to:
    </p>
    <ul class="list-disc ml-8 mb-4">
        <li>To provide and maintain our services</li>
        <li>To respond to inquiries or support requests</li>
        <li>To send you administrative or promotional emails</li>
        <li>To analyze and improve the Site's content and user experience</li>
        <li>To comply with legal obligations</li>
    </ul>
    <!-- Data Retention -->
    <h2 class="text-2xl font-semibold my-4">Data Retention</h2>
    <p class="mb-4">
        We will retain your personal information only for as long as necessary to fulfill the purposes for which it was collected and to comply with applicable laws. After that, we will securely delete your information.
    </p>
    <!-- Third-Party Services -->
    <h2 class="text-2xl font-semibold my-4">Third-Party Services</h2>
    <p class="mb-4">
        We may use third-party services to help us operate and improve our Site. These third-party service providers may have access to your personal information but are obligated not to disclose or use it for any other purpose.
    </p>
    <!-- Security -->
    <h2 class="text-2xl font-semibold my-4">Security</h2>
    <p class="mb-4">
        We prioritize the security of your personal information and follow industry best practices to protect it. However, no method of transmission over the internet or electronic storage is 100% secure, so we cannot guarantee absolute security.
    </p>
    <!-- Your Rights -->
    <h2 class="text-2xl font-semibold my-4">Your Rights</h2>
    <p class="mb-4">
        You have the right to access, correct, or delete your personal information. If you wish to exercise any of these rights, please contact us using the information provided below. We will respond to your request within a reasonable timeframe.
    </p>
    <!-- Changes to This Privacy Policy -->
    <h2 class="text-2xl font-semibold my-4">Changes to This Privacy Policy</h2>
    <p class="mb-4">
        We may update our Privacy Policy from time to time. Any changes will be posted on this page with a revised date. We encourage you to review this Privacy Policy periodically for any updates.
    </p>
    <!-- Contact Us -->
    <h2 class="text-2xl font-semibold my-4">Contact Us</h2>
    <p class="mb-4">
        If you have any questions or concerns about this Privacy Policy, please contact us at <?php echo $gc['contact_email']; ?>.
    </p>
</div>
<!-- your footer code goes here -->

<?php require_once $gc['path']['root'] . '/inc/footer.php'; ?>