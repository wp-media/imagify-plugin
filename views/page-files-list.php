<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<div class="wrap imagify-files-list">

	<?php $this->print_template( 'part-files-list-header' ); ?>

		<form id="imagify-files-list-form" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( Imagify_Views::get_instance()->get_files_page_slug() ); ?>"/>
			<?php $this->list_table->views(); ?>
			<?php $this->list_table->display(); ?>
		</form>

</div>
<?php
