<div class="betslip-item" data-outcome-id="{{ $item['outcome_id'] }}" style="padding: 12px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); margin-bottom: 10px; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <strong style="color: white; font-size: 0.95rem;">{{ $item['outcome_name'] }}</strong><br>
            <small style="color: var(--muted);">{{ $item['market_title'] }}</small><br>
            <small style="color: var(--accent); font-size: 0.8rem;">{{ $item['game_title'] }}</small>
        </div>
        <button class="remove-betslip-item" data-outcome-id="{{ $item['outcome_id'] }}" style="background: none; border: none; color: #ff6b6b; cursor: pointer; font-size: 1.1rem; padding: 0 5px;">&times;</button>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
        <div>
            <span style="font-size: 0.85rem; color: var(--muted);">Odds:</span>
            <strong class="outcome-odds" style="color: var(--accent);">{{ number_format($item['odds'], 2) }}</strong>
        </div>
        <div style="display: flex; align-items: center; gap: 5px;">
            <span style="font-size: 0.85rem; color: var(--muted);">Stake:</span>
            <input type="number" class="betslip-stake" data-outcome-id="{{ $item['outcome_id'] }}" data-odds="{{ $item['odds'] }}" value="{{ $item['stake_amount'] }}" style="width: 70px; padding: 4px 8px; background: #121212; border: 1px solid var(--border); border-radius: 4px; color: white; text-align: right;" min="1">
        </div>
    </div>
</div>
