<?php
/**
 * SEO Optimization Class - 100% SEO Features
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UGST_SEO {
    
    public function init() {
        // Only run if SEO is enabled
        if (get_option('ugst_enable_seo', 'yes') !== 'yes') {
            return;
        }
        
        // Meta tags and schema
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('wp_head', array($this, 'add_schema_markup'));
        add_action('wp_head', array($this, 'add_open_graph_tags'));
        add_action('wp_head', array($this, 'add_twitter_cards'));
        
        // Sitemap generation
        add_action('init', array($this, 'generate_sitemap'));
        add_action('template_redirect', array($this, 'serve_sitemap'));
        
        // Breadcrumbs
        add_action('wp_head', array($this, 'add_breadcrumb_schema'));
        
        // Canonical URLs
        add_action('wp_head', array($this, 'add_canonical_urls'));
        
        // Page speed optimization
        add_action('wp_enqueue_scripts', array($this, 'optimize_page_speed'));
        
        // Mobile optimization
        add_action('wp_head', array($this, 'add_mobile_optimization'));
        
        // Local SEO
        add_action('wp_head', array($this, 'add_local_business_schema'));
        
        // Voice search optimization
        add_action('wp_head', array($this, 'add_voice_search_optimization'));
        
        // Core Web Vitals optimization
        add_action('wp_head', array($this, 'optimize_core_web_vitals'));
    }
    
    /**
     * Add meta tags for SEO
     */
    public function add_meta_tags() {
        global $post;
        
        // Get current page info
        $title = $this->get_page_title();
        $description = $this->get_page_description();
        $keywords = $this->get_page_keywords();
        $canonical = $this->get_canonical_url();
        
        echo "<!-- Ultra GST Invoice SEO Meta Tags -->\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
        echo '<meta name="googlebot" content="index, follow">' . "\n";
        echo '<meta name="bingbot" content="index, follow">' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        
        // Language and region
        echo '<meta name="language" content="' . get_locale() . '">' . "\n";
        echo '<meta name="geo.region" content="IN">' . "\n";
        echo '<meta name="geo.country" content="India">' . "\n";
        
        // Author and publisher
        echo '<meta name="author" content="' . esc_attr(get_option('ugst_company_name', get_bloginfo('name'))) . '">' . "\n";
        echo '<meta name="publisher" content="' . esc_attr(get_option('ugst_company_name', get_bloginfo('name'))) . '">' . "\n";
        
        // Copyright
        echo '<meta name="copyright" content="© ' . date('Y') . ' ' . esc_attr(get_option('ugst_company_name', get_bloginfo('name'))) . '">' . "\n";
        
        // Page-specific meta tags
        if (is_page('customer-dashboard') || is_page('invoice-portal')) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n"; // Private pages
        }
    }
    
    /**
     * Add structured data schema markup
     */
    public function add_schema_markup() {
        if (get_option('ugst_enable_schema', 'yes') !== 'yes') {
            return;
        }
        
        $schema = array();
        
        // Organization schema
        $schema[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_option('ugst_company_name', get_bloginfo('name')),
            'url' => home_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_option('ugst_company_logo', get_site_icon_url())
            ),
            'contactPoint' => array(
                '@type' => 'ContactPoint',
                'telephone' => get_option('ugst_company_phone', ''),
                'contactType' => 'customer service',
                'availableLanguage' => array('English', 'Hindi')
            ),
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => get_option('ugst_company_address', ''),
                'addressLocality' => get_option('ugst_company_city', ''),
                'addressRegion' => get_option('ugst_company_state', ''),
                'postalCode' => get_option('ugst_company_pincode', ''),
                'addressCountry' => 'IN'
            ),
            'sameAs' => $this->get_social_media_urls()
        );
        
        // Website schema
        $schema[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            )
        );
        
        // Software Application schema
        $schema[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Ultra GST Invoice & Inventory Management',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web Browser',
            'offers' => array(
                '@type' => 'Offer',
                'price' => '999',
                'priceCurrency' => 'INR',
                'priceValidUntil' => date('Y-12-31'),
                'availability' => 'https://schema.org/InStock'
            ),
            'aggregateRating' => array(
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '150',
                'bestRating' => '5',
                'worstRating' => '1'
            ),
            'featureList' => array(
                'GST Invoice Generation',
                'Inventory Management',
                'Customer Management',
                'Payment Processing',
                'Reports & Analytics',
                'Multi-tenant Architecture',
                'Mobile Apps',
                'Voice Commands'
            )
        );
        
        // Service schema
        $schema[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => 'GST Invoice & Inventory Management Service',
            'description' => 'Complete GST compliant invoice and inventory management solution for Indian businesses',
            'provider' => array(
                '@type' => 'Organization',
                'name' => get_option('ugst_company_name', get_bloginfo('name'))
            ),
            'areaServed' => array(
                '@type' => 'Country',
                'name' => 'India'
            ),
            'hasOfferCatalog' => array(
                '@type' => 'OfferCatalog',
                'name' => 'GST Invoice Plans',
                'itemListElement' => array(
                    array(
                        '@type' => 'Offer',
                        'itemOffered' => array(
                            '@type' => 'Service',
                            'name' => 'Basic Plan'
                        ),
                        'price' => '999',
                        'priceCurrency' => 'INR'
                    ),
                    array(
                        '@type' => 'Offer',
                        'itemOffered' => array(
                            '@type' => 'Service',
                            'name' => 'Pro Plan'
                        ),
                        'price' => '1999',
                        'priceCurrency' => 'INR'
                    ),
                    array(
                        '@type' => 'Offer',
                        'itemOffered' => array(
                            '@type' => 'Service',
                            'name' => 'Enterprise Plan'
                        ),
                        'price' => '4999',
                        'priceCurrency' => 'INR'
                    )
                )
            )
        );
        
        // FAQ schema for common questions
        $schema[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array(
                array(
                    '@type' => 'Question',
                    'name' => 'What is GST Invoice Management?',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => 'GST Invoice Management is a comprehensive solution for creating, managing, and tracking GST-compliant invoices for Indian businesses.'
                    )
                ),
                array(
                    '@type' => 'Question',
                    'name' => 'How does the inventory management work?',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => 'Our inventory management system tracks stock levels in real-time, sends low stock alerts, and automatically updates inventory when invoices are created.'
                    )
                ),
                array(
                    '@type' => 'Question',
                    'name' => 'Is the software GST compliant?',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => 'Yes, our software is fully GST compliant and generates invoices that meet all Indian GST requirements including CGST, SGST, and IGST calculations.'
                    )
                )
            )
        );
        
        // Output schema markup
        echo "<!-- Ultra GST Invoice Schema Markup -->\n";
        foreach ($schema as $schema_item) {
            echo '<script type="application/ld+json">' . json_encode($schema_item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }
    
    /**
     * Add Open Graph tags
     */
    public function add_open_graph_tags() {
        $title = $this->get_page_title();
        $description = $this->get_page_description();
        $image = $this->get_page_image();
        $url = $this->get_canonical_url();
        
        echo "<!-- Open Graph Meta Tags -->\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:locale" content="' . get_locale() . '">' . "\n";
        
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }
    }
    
    /**
     * Add Twitter Card tags
     */
    public function add_twitter_cards() {
        $title = $this->get_page_title();
        $description = $this->get_page_description();
        $image = $this->get_page_image();
        
        echo "<!-- Twitter Card Meta Tags -->\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        
        $twitter_handle = get_option('ugst_twitter_handle', '');
        if ($twitter_handle) {
            echo '<meta name="twitter:site" content="@' . esc_attr($twitter_handle) . '">' . "\n";
            echo '<meta name="twitter:creator" content="@' . esc_attr($twitter_handle) . '">' . "\n";
        }
        
        if ($image) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        }
    }
    
    /**
     * Add breadcrumb schema
     */
    public function add_breadcrumb_schema() {
        $breadcrumbs = $this->get_breadcrumbs();
        
        if (empty($breadcrumbs)) {
            return;
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $schema['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url']
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    /**
     * Add canonical URLs
     */
    public function add_canonical_urls() {
        $canonical = $this->get_canonical_url();
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }
    
    /**
     * Optimize page speed
     */
    public function optimize_page_speed() {
        // Preload critical resources
        echo '<link rel="preload" href="' . UGST_PLUGIN_URL . 'assets/css/frontend.css" as="style">' . "\n";
        echo '<link rel="preload" href="' . UGST_PLUGIN_URL . 'assets/js/frontend.js" as="script">' . "\n";
        
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//cdn.jsdelivr.net">' . "\n";
        
        // Preconnect to critical third-party origins
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }
    
    /**
     * Add mobile optimization
     */
    public function add_mobile_optimization() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
        echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
        echo '<meta name="theme-color" content="#0073aa">' . "\n";
        echo '<meta name="msapplication-TileColor" content="#0073aa">' . "\n";
    }
    
    /**
     * Add local business schema
     */
    public function add_local_business_schema() {
        $company_name = get_option('ugst_company_name', '');
        $company_address = get_option('ugst_company_address', '');
        
        if (empty($company_name) || empty($company_address)) {
            return;
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $company_name,
            'image' => get_option('ugst_company_logo', get_site_icon_url()),
            'telephone' => get_option('ugst_company_phone', ''),
            'email' => get_option('ugst_company_email', ''),
            'url' => home_url(),
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => get_option('ugst_company_address', ''),
                'addressLocality' => get_option('ugst_company_city', ''),
                'addressRegion' => get_option('ugst_company_state', ''),
                'postalCode' => get_option('ugst_company_pincode', ''),
                'addressCountry' => 'IN'
            ),
            'geo' => array(
                '@type' => 'GeoCoordinates',
                'latitude' => get_option('ugst_company_latitude', ''),
                'longitude' => get_option('ugst_company_longitude', '')
            ),
            'openingHoursSpecification' => array(
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                'opens' => '09:00',
                'closes' => '18:00'
            ),
            'priceRange' => '₹999-₹4999'
        );
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Add voice search optimization
     */
    public function add_voice_search_optimization() {
        // Add speakable schema for voice search
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'speakable' => array(
                '@type' => 'SpeakableSpecification',
                'cssSelector' => array('.ugst-speakable', 'h1', 'h2', '.ugst-description')
            )
        );
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    /**
     * Optimize Core Web Vitals
     */
    public function optimize_core_web_vitals() {
        // Add resource hints for better performance
        echo '<link rel="preload" href="' . get_template_directory_uri() . '/style.css" as="style">' . "\n";
        
        // Add critical CSS inline for above-the-fold content
        echo '<style id="ugst-critical-css">';
        echo '.ugst-loading { opacity: 0; transition: opacity 0.3s ease; }';
        echo '.ugst-loaded { opacity: 1; }';
        echo '</style>' . "\n";
        
        // Add performance monitoring
        echo '<script>';
        echo 'window.addEventListener("load", function() {';
        echo '  if ("performance" in window) {';
        echo '    var perfData = performance.getEntriesByType("navigation")[0];';
        echo '    if (perfData && perfData.loadEventEnd > 0) {';
        echo '      var loadTime = perfData.loadEventEnd - perfData.fetchStart;';
        echo '      console.log("Page load time:", loadTime + "ms");';
        echo '    }';
        echo '  }';
        echo '});';
        echo '</script>' . "\n";
    }
    
    /**
     * Generate XML sitemap
     */
    public function generate_sitemap() {
        if (get_option('ugst_enable_sitemap', 'yes') !== 'yes') {
            return;
        }
        
        // Generate sitemap on plugin activation or when content changes
        add_action('save_post', array($this, 'regenerate_sitemap'));
        add_action('ugst_invoice_created', array($this, 'regenerate_sitemap'));
        add_action('ugst_customer_created', array($this, 'regenerate_sitemap'));
    }
    
    /**
     * Serve XML sitemap
     */
    public function serve_sitemap() {
        if (isset($_GET['ugst-sitemap']) && $_GET['ugst-sitemap'] === 'xml') {
            header('Content-Type: application/xml; charset=utf-8');
            echo $this->get_sitemap_xml();
            exit;
        }
    }
    
    /**
     * Get sitemap XML content
     */
    private function get_sitemap_xml() {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add homepage
        $sitemap .= $this->get_sitemap_url(home_url(), date('c'), 'daily', '1.0');
        
        // Add important pages
        $pages = array(
            'about' => array('priority' => '0.8', 'changefreq' => 'monthly'),
            'contact' => array('priority' => '0.8', 'changefreq' => 'monthly'),
            'pricing' => array('priority' => '0.9', 'changefreq' => 'weekly'),
            'features' => array('priority' => '0.9', 'changefreq' => 'weekly')
        );
        
        foreach ($pages as $slug => $config) {
            $page = get_page_by_path($slug);
            if ($page) {
                $sitemap .= $this->get_sitemap_url(
                    get_permalink($page->ID),
                    get_the_modified_date('c', $page->ID),
                    $config['changefreq'],
                    $config['priority']
                );
            }
        }
        
        $sitemap .= '</urlset>';
        
        return $sitemap;
    }
    
    /**
     * Get sitemap URL entry
     */
    private function get_sitemap_url($url, $lastmod, $changefreq, $priority) {
        $entry = '  <url>' . "\n";
        $entry .= '    <loc>' . esc_url($url) . '</loc>' . "\n";
        $entry .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
        $entry .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
        $entry .= '    <priority>' . $priority . '</priority>' . "\n";
        $entry .= '  </url>' . "\n";
        
        return $entry;
    }
    
    /**
     * Regenerate sitemap
     */
    public function regenerate_sitemap() {
        // Clear sitemap cache
        delete_transient('ugst_sitemap_xml');
        
        // Ping search engines
        $this->ping_search_engines();
    }
    
    /**
     * Ping search engines about sitemap update
     */
    private function ping_search_engines() {
        $sitemap_url = home_url('/?ugst-sitemap=xml');
        
        $ping_urls = array(
            'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url),
            'https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url)
        );
        
        foreach ($ping_urls as $ping_url) {
            wp_remote_get($ping_url, array('timeout' => 5));
        }
    }
    
    /**
     * Helper methods
     */
    private function get_page_title() {
        if (is_front_page()) {
            return get_bloginfo('name') . ' - ' . get_bloginfo('description');
        } elseif (is_page()) {
            return get_the_title() . ' - ' . get_bloginfo('name');
        } else {
            return wp_get_document_title();
        }
    }
    
    private function get_page_description() {
        global $post;
        
        if (is_front_page()) {
            return 'Complete GST Invoice & Inventory Management Solution for Indian Businesses. Generate GST compliant invoices, manage inventory, track payments, and grow your business with our powerful SaaS platform.';
        } elseif (is_page() && $post) {
            $excerpt = get_the_excerpt($post);
            return $excerpt ?: 'Professional GST invoice and inventory management solution designed for Indian businesses.';
        }
        
        return get_bloginfo('description');
    }
    
    private function get_page_keywords() {
        $keywords = array(
            'GST invoice',
            'inventory management',
            'Indian business software',
            'GST compliance',
            'invoice generator',
            'billing software',
            'accounting software',
            'business management',
            'CGST SGST IGST',
            'HSN code',
            'e-way bill',
            'GST return'
        );
        
        return implode(', ', $keywords);
    }
    
    private function get_canonical_url() {
        global $wp;
        return home_url(add_query_arg(array(), $wp->request));
    }
    
    private function get_page_image() {
        if (has_post_thumbnail()) {
            return get_the_post_thumbnail_url(null, 'large');
        }
        
        return get_option('ugst_default_og_image', get_site_icon_url(1200));
    }
    
    private function get_breadcrumbs() {
        $breadcrumbs = array();
        
        $breadcrumbs[] = array(
            'name' => 'Home',
            'url' => home_url()
        );
        
        if (is_page()) {
            global $post;
            if ($post->post_parent) {
                $parent_id = $post->post_parent;
                $breadcrumbs[] = array(
                    'name' => get_the_title($parent_id),
                    'url' => get_permalink($parent_id)
                );
            }
            
            $breadcrumbs[] = array(
                'name' => get_the_title(),
                'url' => get_permalink()
            );
        }
        
        return $breadcrumbs;
    }
    
    private function get_social_media_urls() {
        return array_filter(array(
            get_option('ugst_facebook_url', ''),
            get_option('ugst_twitter_url', ''),
            get_option('ugst_linkedin_url', ''),
            get_option('ugst_instagram_url', ''),
            get_option('ugst_youtube_url', '')
        ));
    }
}