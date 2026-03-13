<?php

namespace BareWP\Agents;

/**
 * AiCodeAgent
 *
 * This class handles taking natural language prompts from the user and processing
 * them through an LLM to output modular, clean PHP + HTML + TailwindCSS code.
 * It enforces rules about generating functional UI components (hero sections,
 * pricing tables, forms) and injecting WordPress logic without third-party plugins.
 */
class AiCodeAgent
{
    /**
     * @var string The model name to use for generation
     */
    protected string $model;

    /**
     * @var string The API key for the LLM service
     */
    protected string $apiKey;

    public function __construct(string $apiKey = '', string $model = 'gpt-4')
    {
        $this->apiKey = $apiKey ?: (getenv('OPENAI_API_KEY') ?: '');
        $this->model = $model;
    }

    /**
     * Define the system instructions that govern the AI Code Agent's behavior.
     *
     * @return string The system prompt.
     */
    public function getSystemInstructions(): string
    {
        return <<<PROMPT
You are an expert AI Code Agent specialized in generating modular, clean PHP + HTML + TailwindCSS code for the BARE-WP headless WordPress architecture.
Your core objective is to translate natural language prompts (e.g., "Build a SaaS landing page") into fully functional, production-ready code.

STRICT SYSTEM INSTRUCTIONS:
1. Output Format: Your output MUST be strictly well-structured, modular PHP mixed with HTML, using TailwindCSS utility classes for all styling. Do NOT generate custom CSS unless explicitly necessary for dynamic calculations.
2. UI Components & Modularity: Generate robust, reusable UI components (hero sections, pricing tables, forms) as partials. When generating interactive components (e.g., Contact Forms), you MUST generate two layers:
   - View (Frontend): HTML with TailwindCSS.
   - Controller/Handler (Backend): PHP logic to process the request.
3. Logic Injection & Security: For forms and interactive elements, automatically inject necessary WordPress Core logic. This MUST include:
   - Generating a `wp_nonce_field()` in the frontend View.
   - Implementing `wp_verify_nonce()` in the backend Handler.
   - Sanitizing all inputs (e.g., `sanitize_text_field()`) in the Handler.
   - Using standard WP functions for data processing (e.g., `wp_mail()`, `wp_insert_post()`).
4. Plugin Prohibition: You are STRICTLY FORBIDDEN from relying on, suggesting, or using ANY third-party WordPress plugins (e.g., Elementor, ACF, Contact Form 7). All logic must be handled using native WordPress Core functions and standard PHP.
5. Architectural Alignment: The generated code must align with the BARE-WP architecture, meaning it will run in a custom PHP frontend engine that bootstraps WordPress headlessly. Do NOT generate standard WordPress theme files (like `style.css` or `functions.php`). Focus on generating views (templates) and controller handlers.
6. Clean Code: Ensure the code is semantic, accessible, and follows modern PHP and HTML best practices.

Example: If asked for a "Contact Form", you should output:
1. A View file containing a Tailwind-styled HTML form with a `wp_nonce_field()`.
2. A Controller/Handler PHP file that verifies the nonce, sanitizes input, and uses `wp_mail()` to send the message.
PROMPT;
    }

    /**
     * Generate code based on a natural language prompt.
     *
     * In a real implementation, this would make an API call to an LLM provider.
     * For the purpose of this architecture design, it defines the structure and logic.
     *
     * @param string $prompt The natural language request (e.g., "Build a SaaS landing page")
     * @return string The generated code (PHP/HTML/TailwindCSS)
     */
    public function generateCode(string $prompt): string
    {
        $systemInstructions = $this->getSystemInstructions();

        // This is a mock implementation of the API call process.
        // 1. Prepare messages array: ['system' => $systemInstructions, 'user' => $prompt]
        // 2. Call LLM API (e.g., OpenAI API)
        // 3. Extract the generated code from the response.

        // Mocking the generation process...
        $mockedResponse = "<!-- Generated based on prompt: \"{$prompt}\" -->\n";
        $mockedResponse .= "<div class=\"container mx-auto px-4 py-12\">\n";
        $mockedResponse .= "    <h2 class=\"text-3xl font-bold mb-8 text-gray-900\">Generated Content</h2>\n";
        $mockedResponse .= "    <div class=\"grid grid-cols-1 md:grid-cols-3 gap-6\">\n";
        $mockedResponse .= "        <?php\n";
        $mockedResponse .= "        \$args = ['post_type' => 'post', 'posts_per_page' => 3];\n";
        $mockedResponse .= "        \$recent_posts = get_posts(\$args);\n";
        $mockedResponse .= "        foreach (\$recent_posts as \$post) : setup_postdata(\$post);\n";
        $mockedResponse .= "        ?>\n";
        $mockedResponse .= "            <div class=\"bg-white rounded-lg shadow-md p-6\">\n";
        $mockedResponse .= "                <h3 class=\"text-xl font-semibold mb-2\"><?php echo esc_html(get_the_title(\$post)); ?></h3>\n";
        $mockedResponse .= "                <p class=\"text-gray-600 mb-4\"><?php echo wp_trim_words(get_the_excerpt(\$post), 20); ?></p>\n";
        $mockedResponse .= "                <a href=\"<?php echo esc_url(get_permalink(\$post)); ?>\" class=\"text-blue-600 hover:text-blue-800 font-medium\">Read More &rarr;</a>\n";
        $mockedResponse .= "            </div>\n";
        $mockedResponse .= "        <?php endforeach; wp_reset_postdata(); ?>\n";
        $mockedResponse .= "    </div>\n";
        $mockedResponse .= "</div>\n";

        return $mockedResponse;
    }

    /**
     * Process a natural language request and save the output to a specified view file.
     *
     * @param string $prompt The natural language request
     * @param string $destinationPath The path to save the generated code
     * @return bool True if successful, false otherwise
     */
    public function buildAndSave(string $prompt, string $destinationPath): bool
    {
        $code = $this->generateCode($prompt);

        // Ensure directory exists
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($destinationPath, $code) !== false;
    }
}
