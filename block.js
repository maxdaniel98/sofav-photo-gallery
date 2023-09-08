( function ( blocks, element ) {
    var el = element.createElement;

    blocks.registerBlockType( 'sofav-photo-gallery/gallery', {
        title: 'Example: Basic',
        edit: function () {
            return el( 'p', {}, 'Hello World (from the editor).' );
        },
        save: function () {
            return el( 'p', {}, 'Hola mundo (from the frontend).' );
        },
    } );

    const LIST_VARATION_NAME = 'sofav-photo-gallery/gallery-list';
    blocks.registerBlockVariation( 'core/query', {
        name: LIST_VARATION_NAME,
        title: 'Photo Gallery List',
        description: 'Displays a list of all photo galleries',
        isActive: ( { namespace, query } ) => {
            return (
                namespace === LIST_VARATION_NAME
                && query.postType === 'sofav-photo-gallery'
            );
        },
        innerBlocks: [
            [
                'core/post-template',
                {},
                [ [ 'core/post-featured-image', { isLink: true } ], [ 'core/post-title', { isLink: true } ] ],
            ],
            [ 'core/query-pagination' ],
            [ 'core/query-no-results' ],
        ],
        
        icon: "format-gallery" /** An SVG icon can go here*/,
        attributes: {
            namespace: LIST_VARATION_NAME,
            query: {
                perPage: 6,
                pages: 0,
                offset: 0,
                postType: 'sofav-photo-gallery',
                order: 'desc',
                orderBy: 'date',
                author: '',
                search: '',
                exclude: [],
                sticky: '',
                inherit: false,
            },
        },
        allowedControls: [ 'inherit', 'order', 'sticky', 'taxQuery', 'search' ],
        scope: [ 'inserter' ],
        }
    );
} )( window.wp.blocks, window.wp.element );