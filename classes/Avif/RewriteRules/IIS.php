<?php
declare(strict_types=1);

namespace Imagify\Avif\RewriteRules;

use Imagify\WriteFile\AbstractIISDirConfFile;

/**
 * Add and remove rewrite rules to the web.config file to display AVIF images on the site.
 */
class IIS extends AbstractIISDirConfFile {

	/**
	 * Name of the tag used as block delemiter.
	 *
	 * @var string
	 */
	const TAG_NAME = 'Imagify: rewrite rules for avif';

	/**
	 * Get unfiltered new contents to write into the file.
	 *
	 * @source https://github.com/igrigorik/webp-detect/blob/master/iis.config
	 *
	 * @return string
	 */
	protected function get_raw_new_contents() {
		$extensions = $this->get_extensions_pattern();
		$extensions = str_replace( '|avif', '', $extensions );
		$home_root  = wp_parse_url( home_url( '/' ) );
		$home_root  = $home_root['path'];

		return trim( '
<!-- @parent /configuration/system.webServer/rewrite/rules -->
<rule name="' . esc_attr( static::TAG_NAME ) . ' 2">
	<match url="^(' . $home_root . '.+)\.(' . $extensions . ')$" ignoreCase="true" />
	<conditions logicalGrouping="MatchAll">
		<add input="{HTTP_ACCEPT}" pattern="image/avif" ignoreCase="false" />
		<add input="{DOCUMENT_ROOT}/{R:1}{R:2}.avif" matchType="IsFile" />
	</conditions>
	<action type="Rewrite" url="{R:1}{R:2}.avif" logRewrittenUrl="true" />
	<serverVariables>
		<set name="ACCEPTS_AVIF" value="true" />
	</serverVariables>
</rule>

<!-- @parent /configuration/system.webServer/rewrite/outboundRules -->
<rule preCondition="IsAvif" name="' . esc_attr( static::TAG_NAME ) . ' 3">
	<match serverVariable="RESPONSE_Vary" pattern=".*" />
	<action type="Rewrite" value="Accept"/>
</rule>
<preConditions name="' . esc_attr( static::TAG_NAME ) . ' 4">
	<preCondition name="IsAvif">
		<add input="{ACCEPTS_AVIF}" pattern="true" ignoreCase="false" />
	</preCondition>
</preConditions>' );
	}
}
