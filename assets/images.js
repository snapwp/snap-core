var previous = wp.media.view.AttachmentCompat;

/**
 * Extend AttachmentCompat, adding additional logic for delete intermediate checkboxes.
 */
wp.media.view.AttachmentCompat = previous.extend({
	/**
	 * Add additional click handler for deleteIntermediate.
	 * @type {Object}
	 */
	events: {
		'submit':          'preventDefault',
		'change input':    'save',
		'change select':   'save',
		'change textarea': 'save',
		'click .delete-intermediate-button': 'deleteIntermediate'
	},

	/**
	 * Bail early if a delete intermediate checkbox is changed.
	 * @param {Object} event
	 */
    save: function( event ) {
		if ( event.target.name === "[delete-intermediate][]" || event.target.id === 'delete-intermediate-all') {
			return;
		}

		previous.prototype.save.apply( this, arguments );
	},

	/**
	 * Handle a delete intermediate checkbox deletion event.
	 * @param {Object} event
	 */
	deleteIntermediate: function(event) {
		var data = {};

		data['attachments[' + wp.media.frame.model.id + '][delete-intermediate][]'] = [];

		if ( event ) {
			event.preventDefault();
		}

		_.each( this.$el.serializeArray(), function( pair ) {

			if (pair.name == '[delete-intermediate][]')  {
				data[ 'attachments['+wp.media.frame.model.id + ']' + pair.name ].push(pair.value);

			} else {
				data[ pair.name ] = pair.value;
			}
		});

		this.controller.trigger( 'attachment:compat:waiting', ['waiting'] );
		this.model.saveCompat( data ).always( _.bind( this.postSave, this ) );
	}
});