<span class="flex min-w-0 flex-col items-center gap-1 text-center" data-cricket-score>
    @forelse ($scoreDisplay['main'] as $inning)
        <span class="flex flex-wrap items-baseline justify-center gap-x-1.5">
            @if (count($scoreDisplay['main']) > 1)<span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $inning['team'] }}</span>@endif
            <strong class="text-xl leading-tight font-extrabold tabular-nums {{ $status === 'Live' ? 'text-[#dc2e08]' : 'text-[#12324a] dark:text-[#e8edf2]' }}">{{ $inning['score'] }}</strong>
            @if ($inning['overs'] !== null)<span class="text-xs font-medium text-slate-500 dark:text-slate-400">({{ $inning['overs'] }} ov)</span>@endif
        </span>
    @empty
        <strong class="text-lg font-bold text-[#12324a] dark:text-[#e8edf2]">{{ $status === 'Prematch' ? 'vs' : '—' }}</strong>
    @endforelse
    @if ($scoreDisplay['innings'])<span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $scoreDisplay['innings'] }}</span>@endif
    @foreach ($scoreDisplay['previous'] as $previous)
        <span class="block max-w-full text-[10px] leading-snug font-normal text-slate-500 dark:text-slate-400">{{ $previous }}</span>
    @endforeach
</span>
