<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
?>
<div class="wrap imagify-files-list">

	<?php $this->print_template( 'part-files-list-header' ); ?>

		<form id="imagify-files-list-form" method="get">
			<?php $this->list_table->display(); ?>
		</form>

</div>
<?php
