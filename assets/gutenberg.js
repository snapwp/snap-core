window.wp.hooks.addFilter('blocks.registerBlockType', 'snap/hideBlocks', function (settings, name) {
	if (window.snapGutenbergOptions.disabledBlocks.indexOf(name) !== -1) {
		return Object.assign({}, settings, {
			supports: Object.assign({}, settings.supports, { inserter: false })
		})
	}

	return settings
});

window.wp.domReady(function () {
	window.wp.blocks.getBlockTypes().forEach(block => {
		if (block.supports && block.supports.typography) {
			if (window.snapGutenbergOptions.disabledTypographyFeatures.font_style) {
				block.supports.typography.__experimentalFontWeight = false;
				block.supports.typography.__experimentalFontStyle = false;
			}

			if (window.snapGutenbergOptions.disabledTypographyFeatures.letter_spacing) {
				block.supports.typography.__experimentalLetterSpacing = false;
			}

			if (window.snapGutenbergOptions.disabledTypographyFeatures.text_transform) {
				block.supports.typography.__experimentalTextTransform = false;
			}
		}

		if (window.snapGutenbergOptions.disabledTypographyFeatures.drop_cap) {
			if (block && block.supports.__experimentalFeatures && block.supports.__experimentalFeatures.typography && block.supports.__experimentalFeatures.typography.dropCap) {
				block.supports.__experimentalFeatures.typography.dropCap = false;
			}
		}

		if (block.variations && block.variations.length) {
			for (let i = 0; i < block.variations.length; i++) {
				if (window.snapGutenbergOptions.disabledBlocks.indexOf(block.name + '/' + block.variations[i].name) !== -1) {
					wp.blocks.unregisterBlockVariation(block.name, block.variations[i].name)
				} else {
					// console.log(block.name + '/' + block.variations[i].name) // useful for debugging
				}
			}
		}

		if (window.snapGutenbergOptions.disableStyles && block.styles && block.styles.length) {
			for (let i = 0; i < block.styles.length; i++) {
				wp.blocks.unregisterBlockStyle(block.name, block.styles[i].name)
			}
		}
	})
});