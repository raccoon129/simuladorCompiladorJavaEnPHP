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



// Agregar función para actualizar números de línea
function updateLineNumbers() {
    const codeArea = document.getElementById('code');
    const lineNumbers = document.getElementById('line-numbers');
    const lines = codeArea.value.split('\n');
    
    // Actualizar números de línea
    lineNumbers.innerHTML = lines.map((_, index) => index + 1).join('\n');
    
    // Sincronizar scroll
    lineNumbers.scrollTop = codeArea.scrollTop;
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', function() {
    const codeArea = document.getElementById('code');
    
    // Crear contenedor para números de línea
    const lineNumbersContainer = document.createElement('div');
    lineNumbersContainer.id = 'line-numbers';
    codeArea.parentNode.insertBefore(lineNumbersContainer, codeArea);
    
    // Envolver todo en un contenedor
    const wrapper = document.createElement('div');
    wrapper.className = 'code-editor-wrapper';
    codeArea.parentNode.insertBefore(wrapper, lineNumbersContainer);
    wrapper.appendChild(lineNumbersContainer);
    wrapper.appendChild(codeArea);
    
    // Eventos para actualizar números de línea
    codeArea.addEventListener('input', updateLineNumbers);
    codeArea.addEventListener('scroll', function() {
        lineNumbersContainer.scrollTop = this.scrollTop;
    });
    
    // Inicializar números de línea
    updateLineNumbers();
});

// Agregar estilos necesarios
const style = document.createElement('style');
style.textContent = `
    .code-editor-wrapper {
        display: flex;

        border: 1px solid #ddd;
        border-radius: 4px;
    }

    #line-numbers {
        padding: 10px;
        border-right: 1px solid #ddd;
        background: #f0f0f0;
        color: #666;
        text-align: right;
        user-select: none;
        font-family: monospace;
        font-size: 14px;
        line-height: 1.5;
        min-width: 40px;
        white-space: pre;
        overflow-y: hidden;
    }

    #code {
        flex: 1;
        margin: 0;
        border: none;
        padding: 10px;
        font-family: monospace;
        font-size: 14px;
        line-height: 1.5;
        resize: vertical;
        background: transparent;
    }

    #code:focus {
        outline: none;
        background: #fff;
    }
`;
document.head.appendChild(style);

// Mantener los atajos de teclado existentes
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        compile();
    }
});