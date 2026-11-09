@php($letrasGrandes = session('letras_grandes', (bool) auth()->user()?->letras_grandes))
<form method="POST" action="{{ route('letras') }}" class="m-0">
    @csrf
    <button type="submit" class="btn btn-sm {{ $letrasGrandes ? 'btn-dark' : 'btn-outline-secondary' }} fw-bold"
            aria-pressed="{{ $letrasGrandes ? 'true' : 'false' }}"
            title="{{ $letrasGrandes ? 'Voltar ao tamanho normal' : 'Aumentar o tamanho das letras' }}">
        {{ $letrasGrandes ? 'A−' : 'A+' }}<span class="visually-hidden"> Letras grandes</span>
    </button>
</form>
