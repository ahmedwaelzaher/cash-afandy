<div class="mb-3">
    <x-toggle name="referral_program_enabled" :title="__('Referral program')" :value="setting('referral_program_enabled')" />
</div>

<div class="mb-3">
    <x-input type="number" name="referral_reward_amount" :title="__('Referral reward amount')"
        :value="setting('referral_reward_amount')" min="0" step="any" validation="required|numeric|min:0" />
</div>

<div class="mb-3">
    <x-input type="number" name="referral_reward_target" :title="__('Invitations required for the reward')"
        :value="setting('referral_reward_target')" min="1" step="1" validation="required|integer|min:1" />
</div>
