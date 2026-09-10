let audioCtx = null;

function obterAudioContext() {
    if (!audioCtx) {
        // Instancia o objeto de audio  com fall back para navegadores mais antigos.
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }

    //Aqui verificamos se o objeto está realmente rodando
    if (audioCtx.state === 'suspended') {
        //Se não estiver rodando nós mandamos o objeto fazer isso
        audioCtx.resume();
    }

    //Retorna a variavel instanciada
    return audioCtx;
}

// Função para unificar a maniplação do som
function tocarTom({ frequencia, duracao = 0.15, tipoOnda = 'sine', inicioEm = 0, volume = 0.2 }) {
    //Recebe a instancia do objeto
    const ctx = obterAudioContext();
    //Criando o objeto que gera a onda sonora
    const osc = ctx.createOscillator();
    //E o que controla o volume
    const gain = ctx.createGain();


    // Definindo o formato da onda e a sua altarua
    osc.type = tipoOnda;
    osc.frequency.value = frequencia;

    //Relogio interno do Audio context para agendar o inicio de um som e o fim também.
    const tempoInicio = ctx.currentTime + inicioEm;
    const tempoFim = tempoInicio + duracao;


    gain.gain.setValueAtTime(0, tempoInicio);
    gain.gain.linearRampToValueAtTime(volume, tempoInicio + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.001, tempoFim);

    osc.connect(gain).connect(ctx.destination);
    osc.start(tempoInicio);
    osc.stop(tempoFim + 0.02);
}

// Som de sucesso: dois bips curtos e ascendentes
export function tocarSomSucesso() {
    tocarTom({ frequencia: 880, duracao: 0.2 });
    tocarTom({ frequencia: 1175, duracao: 0.15, inicioEm: 0.2 });
}

// Som de erro: um tom grave único
export function tocarSomErro() {
    tocarTom({ frequencia: 225, duracao: 0.25, tipoOnda: 'square', volume: 5.25 });
}