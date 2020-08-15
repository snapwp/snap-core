window.wp.hooks.addFilter('blocks.registerBlockType', 'snap/hideBlocks', function(settings, name) {
	if (window.snapGutenbergOptions.disabledBlocks.indexOf(name) !== -1) {
		return Object.assign({}, settings, {
			supports: Object.assign({}, settings.supports, {inserter: false})
		})
	}

	return settings
})

window.wp.domReady(function() {
	if (window.snapGutenbergOptions.disableDropCaps) {
		var pBlock = window.wp.blocks.getBlockType('core/paragraph');
		if (pBlock) {
			pBlock.supports.__experimentalFeatures.typography.dropCap = false;
		}
	}
});
