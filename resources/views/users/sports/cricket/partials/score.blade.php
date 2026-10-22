<span class="flex min-w-0 flex-col items-center gap-1 text-center" data-cricket-score>
    @forelse ($scoreDisplay['main'] as $inning)
        <span class="flex flex-wrap items-baseline justify-center gap-x-1.5">
            @if (count($scoreDisplay['main']) > 1)<span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $inning['team'] }}</span>@endif
            <strong data-score-status="{{ $status }}" @class(['match-score text-xl leading-tight font-extrabold tabular-nums', 'match-score-on-dark' => $scoreOnDark ?? false])>{{ $inning['score'] }}</strong>
            @if ($inning['overs'] !== null)<span class="text-xs font-medium text-slate-500 dark:text-slate-400">({{ $inning['overs'] }} ov)</span>@endif
        </span>
    @empty
        <strong data-score-status="{{ $status }}" @class(['match-score text-lg font-bold', 'match-score-on-dark' => $scoreOnDark ?? false])>{{ $status === 'Prematch' ? 'vs' : '—' }}</strong>
    @endforelse
    @if ($scoreDisplay['innings'])<span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $scoreDisplay['innings'] }}</span>@endif
    @foreach ($scoreDisplay['previous'] as $previous)
        <span class="block max-w-full text-[10px] leading-snug font-normal text-slate-500 dark:text-slate-400">{{ $previous }}</span>
    @endforeach
</span>
