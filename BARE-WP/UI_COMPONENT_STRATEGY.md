# BARE-WP Modular UI Component Strategy

## Overview

This document outlines the framework for generating **Modular UI Components** within the BARE-WP architecture. The AI Code Agent must adhere to this strategy to ensure that the generated code is highly reusable, easy to maintain, secure, and perfectly integrated with WordPress Core functionality, without relying on any third-party plugins.

## Architecture & Structure

To maintain separation of concerns and reusability, components must be structurally divided into two layers when interactive functionality is required (e.g., forms, dynamic data loading).

1.  **View (Frontend):** The presentation layer composed of HTML and strictly TailwindCSS utility classes.
2.  **Controller / Handler (Backend Logic):** The PHP logic that processes requests, interacts with the database (WordPress Core), and handles security checks (nonces, sanitization, authorization).

Components should be generated as partials that can be included in main page layouts (e.g., `src/Views/components/hero.php`, `src/Controllers/ContactController.php`).

## 1. Styling Strategy: Strict TailwindCSS

The AI agent must **only** use TailwindCSS utility classes for styling generated HTML.

*   **No Custom CSS:** Avoid generating inline styles (`style="..."`) or custom CSS blocks unless strictly necessary for a dynamic calculation that Tailwind cannot handle.
*   **Responsive Design:** Use Tailwind's responsive prefixes (`sm:`, `md:`, `lg:`) to ensure components look great on all devices.
*   **Consistency:** Utilize standard Tailwind color palettes and spacing scales to maintain a cohesive look across the application.

## 2. Reusability and Modularity

Components must be designed to be included multiple times across different pages without conflicting.

*   **PHP Includes:** The primary method for rendering components is via standard PHP `require` or custom template loading functions built into the BARE-WP framework.
*   **Data Passing:** Components should accept dynamic data via variables passed from the parent view or controller. Avoid hardcoding content within the component template whenever possible.
*   **Scope Isolation:** If a component requires specific variables (like `$post` or `$user`), ensure these are clearly documented at the top of the component file or checked before use (`if (isset($data))`).

## 3. Logic Injection & Security (e.g., Forms)

When a user requests an interactive component like a "Contact Form", "Newsletter Signup", or "Custom Search", the AI agent MUST generate both the frontend presentation and the backend processing logic.

### 3.1 The View (Frontend Form)
The generated form must include:
*   Appropriate HTML structure with Tailwind styling.
*   Proper input types and accessibility attributes (labels, ARIA).
*   **Crucially:** A WordPress Nonce field for security.

*Example Form Snippet (`src/Views/components/contact-form.php`):*
```php
<form action="/contact-submit" method="POST" class="max-w-lg mx-auto bg-white p-8 rounded shadow-md">
    <!-- Security Nonce -->
    <?php wp_nonce_field('barewp_contact_submit', 'barewp_contact_nonce'); ?>

    <div class="mb-4">
        <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
        <input type="text" id="name" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
    </div>
    <div class="mb-6">
        <label for="message" class="block text-gray-700 text-sm font-bold mb-2">Message</label>
        <textarea id="message" name="message" required rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
    </div>
    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
        Send Message
    </button>
</form>
```

### 3.2 The Handler (Backend PHP Logic)
The generated handler logic must securely process the submission using native WordPress functions.

The handler MUST implement:
1.  **Nonce Verification:** Verify the request originated from the intended form (`wp_verify_nonce`).
2.  **Sanitization:** Clean all input data before processing (`sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`).
3.  **Action / Core Logic Injection:** Execute the required WordPress core function (e.g., `wp_mail()` for sending an email, `wp_insert_post()` to save a form submission as a custom post type, or user registration functions).
4.  **Feedback:** Provide appropriate success or error responses (redirecting with a query parameter or returning JSON if handling via AJAX).

*Example Handler Snippet (`src/Controllers/ContactController.php`):*
```php
<?php
namespace BareWP\Controllers;

class ContactController
{
    public function handleSubmission()
    {
        // 1. Verify Request Method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // 2. Verify Nonce
        if (!isset($_POST['barewp_contact_nonce']) || !wp_verify_nonce($_POST['barewp_contact_nonce'], 'barewp_contact_submit')) {
            wp_die('Security check failed.', 'Error', ['response' => 403]);
        }

        // 3. Sanitize Input
        $name = sanitize_text_field($_POST['name'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        // Validate
        if (empty($name) || empty($message)) {
            // Handle validation error (e.g., redirect back with error flag)
            wp_redirect('/contact?error=missing_fields');
            exit;
        }

        // 4. Execute Core Logic (e.g., Send Email or Save Post)
        // Option A: Send Email
        $to = get_option('admin_email');
        $subject = 'New Contact Submission from ' . $name;
        $body = "Name: \$name\n\nMessage:\n\$message";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($to, $subject, $body, $headers);

        // Option B: Save as a Custom Post Type (if required by prompt)
        /*
        wp_insert_post([
            'post_title'   => 'Contact from ' . $name,
            'post_content' => $message,
            'post_status'  => 'private',
            'post_type'    => 'contact_submission'
        ]);
        */

        // 5. Redirect on Success
        wp_redirect('/contact?success=1');
        exit;
    }
}
```

## Summary for AI Agent Generation

When generating interactive components, the AI Agent must output:
1.  **The View File:** Containing HTML, Tailwind classes, and the `wp_nonce_field()`.
2.  **The Controller/Handler File:** Containing PHP logic to verify nonces, sanitize input, and execute native WordPress functions for data processing.
3.  **Instructions:** Brief instructions on how the view should be included and how the controller route should be mapped in the BARE-WP custom frontend engine.
