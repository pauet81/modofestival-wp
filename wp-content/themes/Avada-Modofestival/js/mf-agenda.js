document.addEventListener('DOMContentLoaded', function () {
    const mfLoadingOverlay = document.getElementById('mf-loading-overlay');
    const mfLoadingText = document.getElementById('mf-loading-text');

    const mfLoadingPhrases = [
        'Buscando el próximo festival que te cambiará el verano…',
        'Afinando guitarras y montando escenarios por toda la península…',
        'Revisando carteles y cuadrando fechas para tu escapada perfecta…',
        'Probando sonido en todos los escenarios antes de abrir puertas…',
        'Subiendo el volumen de la agenda de festivales…',
        'Conectando amplis, luces y destinos festivaleros…',
        'Cargando festivales y desenredando cables entre estilos y ciudades…',
        'Poniendo en fila artistas, estilos y ciudades para ti…',
        'Calentando motores para tu próxima maratón de directos…',
        'Localizando festivales donde la resaca merece la pena…'
    ];

    window.mfShowLoader = function () {
        if (!mfLoadingOverlay || !mfLoadingText) return;
        const phrase = mfLoadingPhrases[Math.floor(Math.random() * mfLoadingPhrases.length)];
        mfLoadingText.textContent = phrase;
        mfLoadingOverlay.classList.add('mf-loading-overlay--visible');
    };

    window.mfHideLoader = function () {
        if (!mfLoadingOverlay) return;
        mfLoadingOverlay.classList.remove('mf-loading-overlay--visible');
    };
});
