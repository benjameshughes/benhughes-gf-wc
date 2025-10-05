<?php
/**
 * Cache Invalidation Hooks
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Services;

use BenHughes\GravityFormsWC\Repositories\CachedFormRepository;
use BenHughes\GravityFormsWC\Repositories\CachedProductRepository;

class CacheInvalidation {

    private CachedProductRepository $product_cache;
    private CachedFormRepository $form_cache;

    public function __construct( CachedProductRepository $product_cache, CachedFormRepository $form_cache ) {
        $this->product_cache = $product_cache;
        $this->form_cache    = $form_cache;

        // Invalidate on product save/update
        add_action( 'save_post_product', [ $this, 'on_product_save' ], 10, 3 );
        add_action( 'woocommerce_update_product', [ $this, 'on_product_update' ], 10, 1 );

        // Invalidate on GF form save
        add_action( 'gform_after_save_form', [ $this, 'on_form_save' ], 10, 2 );
    }

    public function on_product_save( int $post_id, $post, $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        if ( $post_id > 0 ) {
            $this->product_cache->clearCache( $post_id );
        }
    }

    public function on_product_update( int $product_id ): void {
        if ( $product_id > 0 ) {
            $this->product_cache->clearCache( $product_id );
        }
    }

    public function on_form_save( $form, bool $is_new ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        $form_id = is_array( $form ) && isset( $form['id'] ) ? (int) $form['id'] : 0;
        if ( $form_id > 0 ) {
            $this->form_cache->clearCache( $form_id );
        }
    }
}

