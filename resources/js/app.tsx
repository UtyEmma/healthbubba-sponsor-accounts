import { createInertiaApp } from '@inertiajs/react';
import '@fontsource-variable/inter';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#40B93C',
    },
});
