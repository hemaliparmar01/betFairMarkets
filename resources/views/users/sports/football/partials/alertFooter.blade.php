@if (request()->input('type') === 'prematch_drops')
<footer class="prematch-drops__legend">
                <span class="prematch-drops__legend-item">
                    <span class="prematch-drops__legend-dot prematch-drops__legend-dot--new"></span>
                    <strong>NEW</strong>
                    – Drop just detected
                </span>

                <span class="prematch-drops__legend-item">
                    <span class="prematch-drops__legend-dot prematch-drops__legend-dot--active"></span>
                    <strong>ACTIVE</strong>
                    – Drop is continuing
                </span>

                <span class="prematch-drops__legend-item">
                    <span class="prematch-drops__legend-dot prematch-drops__legend-dot--weak"></span>
                    <strong>WEAK</strong>
                    – Drop is weakening or reversing
                </span>
            </footer>
@else
<footer class="market-shock-detector__footer"><span>ⓘ</span><span>{{ __('Alerts are automatically removed after 20 minutes') }}</span></footer>
@endif
