<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<tr id="{{ data.groupId }}-{{ data.id }}">
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
	<td class="imagify-cell-original">{{ data.original_size_human }}</td>
	<td class="imagify-cell-optimized">{{ data.new_size_human }}</td>
	<td class="imagify-cell-percentage">
		<span class="imagify-chart">
			<span class="imagify-chart-container">
				<canvas height="18" width="18" id="imagify-consumption-chart-{{ data.chartSuffix }}"></canvas>
			</span>
		</span>
		<span class="imagipercent">{{ data.percent_human }}</span>
	</td>
	<td class="imagify-cell-savings">{{ data.overall_saving_human }}</td>
</tr>
