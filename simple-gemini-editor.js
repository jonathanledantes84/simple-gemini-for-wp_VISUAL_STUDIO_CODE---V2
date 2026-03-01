(function(wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar } = wp.editPost;
    const { PanelBody, TextareaControl, Button, SelectControl, Spinner, Notice } = wp.components;
    const { useState } = wp.element;
    const { useDispatch, useSelect } = wp.data;

    const GeminiSidebar = () => {
        const [prompt, setPrompt] = useState('');
        const [model, setModel] = useState('gemini-2.5-flash');
        const [loading, setLoading] = useState(false);
        const [error, setError] = useState('');
        const [success, setSuccess] = useState('');

        const { insertBlocks } = useDispatch('core/block-editor');
        const selectedBlockClientId = useSelect(select => 
            select('core/block-editor').getSelectedBlockClientId()
        );

        const generate = (full = false) => {
            if (!prompt.trim()) {
                setError('Escribí un prompt primero, loco.');
                return;
            }

            setLoading(true);
            setError('');
            setSuccess('');

            wp.ajax.post(full ? 'simple_gemini_full' : 'simple_gemini_generate', {
                nonce: simpleGeminiData.nonce,
                prompt: prompt,
                model: model
            })
            .done(res => {
                if (res.success) {
                    const newBlock = wp.blocks.createBlock('core/paragraph', {
                        content: (res.data.text || res.data.content || '').replace(/\n/g, '<br>')
                    });

                    if (selectedBlockClientId) {
                        insertBlocks(newBlock, selectedBlockClientId);
                    } else {
                        insertBlocks(newBlock);
                    }

                    if (full && res.data.post_id) {
                        setSuccess(`¡Post completo creado! ID: ${res.data.post_id}. Revisalo en borradores.`);
                    } else {
                        setSuccess('Texto generado e insertado.');
                    }
                } else {
                    setError(res.data?.message || 'Algo falló en el servidor.');
                }
            })
            .fail(err => {
                setError(err.message || 'Error de conexión. Revisá tu API key o internet.');
            })
            .always(() => setLoading(false));
        };

        return (
            <PluginSidebar
                name="gemini-sidebar"
                title="Gemini IA - General Roca"
                icon="lightbulb"
            >
                <PanelBody title="Generar con Gemini">
                    <TextareaControl
                        label="Prompt"
                        value={prompt}
                        onChange={setPrompt}
                        rows={6}
                        placeholder="Ej: nota policial Alto Valle hoy, o resumen de la Fiesta de la Manzana..."
                    />
                    <SelectControl
                        label="Modo"
                        value={model}
                        options={[
                            { label: 'Texto rápido (gemini-2.5-flash)', value: 'gemini-2.5-flash' },
                            { label: 'Texto + imagen (más lento)', value: 'gemini-2.5-flash-image' }
                        ]}
                        onChange={setModel}
                    />
                    <div style={{ marginTop: '15px', display: 'flex', gap: '10px' }}>
                        <Button
                            isPrimary
                            onClick={() => generate(false)}
                            disabled={loading || !prompt.trim()}
                        >
                            {loading ? <Spinner /> : 'Generar texto'}
                        </Button>
                        <Button
                            isSecondary
                            onClick={() => generate(true)}
                            disabled={loading || !prompt.trim()}
                        >
                            {loading ? <Spinner /> : 'Post completo + imagen'}
                        </Button>
                    </div>

                    {error && <Notice status="error" isDismissible={false}>{error}</Notice>}
                    {success && <Notice status="success" isDismissible={false}>{success}</Notice>}
                </PanelBody>
            </PluginSidebar>
        );
    };

    registerPlugin('simple-gemini', {
        render: GeminiSidebar,
        icon: 'lightbulb'
    });

})(window.wp);