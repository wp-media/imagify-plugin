<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<tr id="{{ data.groupID }}-{{ data.mediaID }}">
	<td class="imagify-cell-filename">
		<span class="imagiuploaded"><img src="{{ data.thumbnail }}" alt=""/></span>
		<span class="imagifilename">{{ data.filename }}</span>
	</td>
	<td class="imagify-cell-status">
		<span class="imagistatus status-{{ data.status }}">
			<span class="dashicons dashicons-{{ data.icon }}"></span>
			{{ data.label }}
		</span>
	</td>
	<td class="imagify-cell-thumbnails">{{ data.thumbnailsCount }}</td>
	<td class="imagify-cell-original-size">{{ data.originalSizeHuman }}</td>
	<td class="imagify-cell-optimized-size">{{ data.newSizeHuman }}</td>
	<td class="imagify-cell-percentage">
		<span class="imagify-chart">
			<span class="imagify-chart-container">
				<canvas height="18" width="18" id="imagify-consumption-chart-{{ data.mediaID }}"></canvas>
			</span>
		</span>
		<span class="imagipercent">{{ data.percentHuman }}</span>
	</td>
	<td class="imagify-cell-savings">{{ data.overallSavingHuman }}</td>
</tr>
