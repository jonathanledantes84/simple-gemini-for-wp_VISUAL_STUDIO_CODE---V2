const { registerPlugin } = wp.plugins;
const { PluginSidebar } = wp.editPost;
const { Button } = wp.components;

const PublishDirectButton = () => {
    const publishDirect = () => {
        wp.data.dispatch('core/editor').editPost({ status: 'publish' });
        wp.data.dispatch('core/editor').savePost();
        alert('✅ Publicado directamente!');
    };

    return wp.element.createElement(
        PluginSidebar,
        { name: 'gemini-sidebar', title: 'Gemini Tools' },
        wp.element.createElement(
            Button,
            { isPrimary: true, onClick: publishDirect },
            '🚀 Publicar directo'
        )
    );
};

registerPlugin('gemini-sidebar', { render: PublishDirectButton });