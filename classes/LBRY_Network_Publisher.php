<?php
/**
 * Class for publishing to the LBRY Network
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

class LBRY_Network_Publisher
{
    /**
     * Publish the post to the LBRY Network
     * @param  WP_POST      $post_id  The ID of the post we are publishing
     * @param  string   $channel The Claim ID of the channel we are posting to
     */
    // NOTE: This is currently sitting at about 150ms, mostly the post parsing
    public function publish( $post, $channel, $license ) {
        
        $post_id = $post->ID;
        // Get converted markdown into a file
        $filepath = LBRY_ABSPATH . 'tmp/' . $post->post_name . time() . '.md';
        $file = fopen( $filepath, 'w' );
        $converted = LBRY()->network->parser->convert_to_markdown( $post );
        $write_status = $file && fwrite( $file, $converted );
        fclose( $file );

        try {
            if (!$write_status) {
                throw new \Exception( 'Write Status failure', 1 );
            }

            // If everything went well with the conversion, carry on
            $args = array(
                'name' => $post->post_name,
                'bid' => number_format( floatval( get_option( LBRY_SETTINGS )[LBRY_LBC_PUBLISH] ), 3, '.', '' ),
                'file_path' => $filepath,
                'title' => $post->post_title,
                'languages' => array( substr( get_locale(), 0, 2 ) ),
                'license' => $license,
            );

            // Setup channel
            if ( $channel && $channel != 'null' ) {
                $args['channel_id'] = $channel;
            }

            // Setup featured image
            $featured_id = get_post_thumbnail_id( $post );
            $featured_image = wp_get_attachment_image_src( $featured_id, 'medium' );

            if ( $featured_image[0] ) {
                $args['thumbnail_url'] = $featured_image[0];
            }

            // Setup Tags
            $tags = get_the_terms( $post, 'post_tag' );
            if ( $tags ) {
                $tag_names = [];
                foreach ( $tags as $tag ) {
                    $tag_names[] = $tag->name;
                }
                $args['tags'] = $tag_names;
            }

            // Build description using Yoast if installed and its used, excerpt/title otherwise
            $description = false;
            if ( class_exists( 'WPSEO_META' ) ) {
                $description = WPSEO_META::get_value( 'opengraph-description', $post->ID );
            }
            if ( ! $description ) {
                $excerpt = get_the_excerpt( $post );
                $description = $excerpt ? $excerpt : $post->post_title;
            }
            $description .= ' | Originally published at ' . get_permalink( $post );

            $args['description'] = $description;

            $result = LBRY()->daemon->publish( $args );
            $outputs = $result->outputs;

            if ( $outputs && is_array( $outputs ) ) {
                $output = $result->outputs[0];
                $claim_id = $output->claim_id;
                // Set Claim ID
                update_post_meta( $post->ID, LBRY_CLAIM_ID, $claim_id );

                // Set Canonical URL
                $canonical_url = LBRY()->daemon->canonical_url( $claim_id );
                update_post_meta( $post->ID, LBRY_CANONICAL_URL, $canonical_url );
            }
        } catch (Exception $e) {
            error_log( 'Issue publishing post ' . $post->ID . ' to LBRY: ' .  $e->getMessage() );
        } finally {
            //Delete the temporary markdown file
            unlink( $filepath );
        }
    }
}
