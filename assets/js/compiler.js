function compile() {
    const code = document.getElementById('code').value;
    
    // Mostrar indicadores de carga
    document.getElementById('analisis-lexico').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
    document.getElementById('analisis-sintactico').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-success" role="status"></div></div>';
    document.getElementById('analisis-semantico').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-info" role="status"></div></div>';
    document.getElementById('output').innerHTML = 'Compilando...';
    document.getElementById('console-output').innerHTML = 'Ejecutando...';
    
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'code=' + encodeURIComponent(code)
    })
    .then(response => response.json())
    .then(data => {
        // Actualizar cada sección con su análisis correspondiente
        document.getElementById('analisis-lexico').innerHTML = data.lexicoHTML;
        document.getElementById('analisis-sintactico').innerHTML = data.sintacticoHTML;
        document.getElementById('analisis-semantico').innerHTML = data.semanticoHTML;
        document.getElementById('output').innerHTML = data.result.compilacion;
        document.getElementById('console-output').innerHTML = data.result.consola || 'No hay salida en consola';
    })
    .catch(error => {
        document.getElementById('output').innerHTML = 'Error: ' + error;
        document.getElementById('console-output').innerHTML = 'Error en la ejecución';
        // Mostrar mensaje de error en las secciones de análisis
        const errorMsg = '<div class="text-danger p-3">Error al procesar el código</div>';
        document.getElementById('analisis-lexico').innerHTML = errorMsg;
        document.getElementById('analisis-sintactico').innerHTML = errorMsg;
        document.getElementById('analisis-semantico').innerHTML = errorMsg;
    });
}