<div class="content-section">
    <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
        <span>💰 Finances <span style="font-size: 0.55em; font-weight: normal; color: #888; margin-left: 8px;">What each player owes or is due</span></span>
        <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('finances-body-{{ $league->id }}')" id="toggle-finances-body-{{ $league->id }}">&#9650;</span>
    </h2>

    <div id="finances-body-{{ $league->id }}">
        @if(empty($playerSummaries))
            <div style="text-align: center; padding: 40px; color: #888;">No players in this league yet.</div>
        @else
            {{-- League-wide summary --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 18px;">
                <div style="padding: 14px; background: var(--primary-light); border-radius: 8px; text-align: center;">
                    <div style="font-size: 0.8em; color: #888; margin-bottom: 4px;">Fees Outstanding</div>
                    <div style="font-size: 1.25em; font-weight: 700; color: {{ $totals['fees_outstanding'] > 0 ? '#dc3545' : '#28a745' }};">${{ number_format($totals['fees_outstanding'], 2) }}</div>
                </div>
                <div style="padding: 14px; background: var(--primary-light); border-radius: 8px; text-align: center;">
                    <div style="font-size: 0.8em; color: #888; margin-bottom: 4px;">Fees Collected</div>
                    <div style="font-size: 1.25em; font-weight: 700; color: #333;">${{ number_format($totals['fees_paid'], 2) }}</div>
                </div>
                <div style="padding: 14px; background: var(--primary-light); border-radius: 8px; text-align: center;">
                    <div style="font-size: 0.8em; color: #888; margin-bottom: 4px;">Winnings Awarded</div>
                    <div style="font-size: 1.25em; font-weight: 700; color: #333;">${{ number_format($totals['winnings'], 2) }}</div>
                </div>
                <div style="padding: 14px; background: var(--primary-light); border-radius: 8px; text-align: center;">
                    <div style="font-size: 0.8em; color: #888; margin-bottom: 4px;">Paid Out</div>
                    <div style="font-size: 1.25em; font-weight: 700; color: #333;">${{ number_format($totals['payouts'], 2) }}</div>
                </div>
            </div>

            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 1%; white-space: nowrap;">Player</th>
                            <th style="text-align: right;">Fees Owed</th>
                            <th style="text-align: right;">Fees Paid</th>
                            <th style="text-align: right;">Winnings</th>
                            <th style="text-align: right;">Paid Out</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playerSummaries as $summary)
                            <tr>
                                <td style="white-space: nowrap; font-weight: 600;">{{ $summary['player']->name }}</td>
                                <td style="text-align: right; color: {{ $summary['fees_owed'] > 0 ? '#dc3545' : '#888' }};">${{ number_format($summary['fees_owed'], 2) }}</td>
                                <td style="text-align: right;">${{ number_format($summary['fees_paid'], 2) }}</td>
                                <td style="text-align: right;">${{ number_format($summary['winnings'], 2) }}</td>
                                <td style="text-align: right;">${{ number_format($summary['payouts'], 2) }}</td>
                                <td style="text-align: right; font-weight: 700;">
                                    @if($summary['balance'] < -0.001)
                                        <span style="color: #dc3545;">Owes ${{ number_format(abs($summary['balance']), 2) }}</span>
                                    @elseif($summary['balance'] > 0.001)
                                        <span style="color: #28a745;">Due ${{ number_format($summary['balance'], 2) }}</span>
                                    @else
                                        <span style="color: #888;">Settled</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: 700; border-top: 2px solid var(--primary-color); background: var(--primary-light);">
                            <td>Totals</td>
                            <td style="text-align: right; color: {{ $totals['fees_outstanding'] > 0 ? '#dc3545' : '#888' }};">${{ number_format($totals['fees_outstanding'], 2) }}</td>
                            <td style="text-align: right;">${{ number_format($totals['fees_paid'], 2) }}</td>
                            <td style="text-align: right;">${{ number_format($totals['winnings'], 2) }}</td>
                            <td style="text-align: right;">${{ number_format($totals['payouts'], 2) }}</td>
                            <td style="text-align: right; color: {{ $totals['balance'] < 0 ? '#dc3545' : '#28a745' }};">
                                ${{ number_format(abs($totals['balance']), 2) }}{{ $totals['balance'] < 0 ? ' deficit' : '' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="margin-top: 12px; font-size: 0.8em; color: #999; line-height: 1.5;">
                <strong>Status</strong> is each player's net balance — fees, winnings, and payouts combined.
                A player who <span style="color: #dc3545; font-weight: 600;">Owes</span> still needs to pay the league;
                one who is <span style="color: #28a745; font-weight: 600;">Due</span> is owed money by the league.
            </div>
        @endif
    </div>
</div>
