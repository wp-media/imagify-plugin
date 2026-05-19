import { wpCli } from './wp-cli';

/**
 * Creates a seeded WordPress attachment that Imagify considers optimised but
 * missing its next-gen (WebP) version.  This makes the
 * "Generate missing next-gen images versions" button appear on the settings
 * page and guarantees at least one attachment exists in the media library.
 *
 * Idempotent: if the seed attachment already exists it is reused.
 * Returns the attachment post ID.
 *
 * Implementation notes
 * --------------------
 * The PHP code uses double-quoted strings throughout so it can be safely
 * passed to `wp eval '...'` (single-quoted shell strings forbid inner
 * single quotes).
 *
 * The SQL query that drives the button visibility requires:
 *   1. attachment post with a non-webp mime type
 *   2. _imagify_status = "success" | "already_optimized"
 *   3. _imagify_data NOT containing `@imagify-webp";a:4:{s:7:"success";b:1;`
 *   4. physical backup file at get_imagify_attachment_backup_path()
 */
export function seedOptimizedImage(): number {
	const php = `
$title = "Imagify E2E Test Seed";
$existing = get_posts([
    "post_type"   => "attachment",
    "title"       => $title,
    "numberposts" => 1,
    "fields"      => "ids",
    "post_status" => "any",
]);
if ( $existing ) { echo $existing[0]; return; }

$gd = imagecreatetruecolor( 100, 100 );
$white = imagecolorallocate( $gd, 255, 255, 255 );
imagefill( $gd, 0, 0, $white );
$upload    = wp_upload_dir();
$file_path = $upload["path"] . "/imagify-e2e-seed.jpg";
imagejpeg( $gd, $file_path, 90 );
imagedestroy( $gd );

$id = wp_insert_attachment([
    "post_mime_type" => "image/jpeg",
    "post_title"     => $title,
    "post_content"   => "",
    "post_status"    => "inherit",
], $file_path );

require_once ABSPATH . "wp-admin/includes/image.php";
$meta = wp_generate_attachment_metadata( $id, $file_path );
wp_update_attachment_metadata( $id, $meta );

update_post_meta( $id, "_imagify_status", "success" );
update_post_meta( $id, "_imagify_data", [
    "sizes" => [
        "full" => [
            "success"        => true,
            "original_size"  => 8000,
            "optimized_size" => 6000,
            "percent"        => 25.0,
        ],
    ],
    "stats" => [
        "original_size"  => 8000,
        "optimized_size" => 6000,
        "percent"        => 25.0,
    ],
]);

$backup = get_imagify_attachment_backup_path( $file_path );
if ( $backup ) {
    wp_mkdir_p( dirname( $backup ) );
    copy( $file_path, $backup );
}

delete_transient( "imagify_stat_without_next_gen" );
echo $id;
`;

	const result = wpCli( `eval '${ php }'` );
	return parseInt( result.trim(), 10 );
}
