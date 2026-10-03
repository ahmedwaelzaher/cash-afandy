<section class="site-cashback-steps py-5">
    <div class="container">
        <div class="site-deals-heading text-center text-md-start mb-5">
            <h2 class="mb-1">{{ __('How do you get cashback') }}</h2>
            <p class="text-body-secondary mb-0">
                {{ __('All you have to do is follow the next steps to get a cash refund on your purchases') }}
            </p>
        </div>

        <div class="row">
            <div class="col-6 col-lg-3 text-center mb-4 mb-lg-0">
                <div class="site-cashback-orbit mx-auto mb-3" style="--step-color: var(--Green-Lightest)">
                    <span class="site-cashback-node site-cashback-node-start"></span>
                    <span class="site-cashback-node site-cashback-node-end"></span>

                    <div class="site-cashback-circle">
                        <span class="site-cashback-number">01</span>
                    </div>
                </div>

                <h4 class="mb-2">{{ __('Sign up on :app_name', ['app_name' => app_name()]) }}</h4>
                <p class="text-body-secondary small mb-0">
                    {{ __('Easily create a new account on :app_name or log in if you already have an account', ['app_name' => app_name()]) }}
                </p>
            </div>

            <div class="col-6 col-lg-3 text-center mb-4 mb-lg-0">
                <div class="site-cashback-orbit site-cashback-orbit-reverse mx-auto mb-3"
                    style="--step-color: var(--Green-Light)">
                    <span class="site-cashback-node site-cashback-node-start"></span>
                    <span class="site-cashback-node site-cashback-node-end"></span>

                    <div class="site-cashback-circle">
                        <span class="site-cashback-number">02</span>
                    </div>
                </div>

                <h4 class="mb-2">{{ __('Browse the cashback section') }}</h4>
                <p class="text-body-secondary small mb-0">
                    {{ __('You will find many featured stores on :app_name, all you have to do is choose one of them', ['app_name' => app_name()]) }}
                </p>
            </div>

            <div class="col-6 col-lg-3 text-center mb-4 mb-lg-0">
                <div class="site-cashback-orbit mx-auto mb-3" style="--step-color: var(--Green-Dark)">
                    <span class="site-cashback-node site-cashback-node-start"></span>
                    <span class="site-cashback-node site-cashback-node-end"></span>

                    <div class="site-cashback-circle">
                        <span class="site-cashback-number">03</span>
                    </div>
                </div>

                <h4 class="mb-2">{{ __('Complete the purchase') }}</h4>
                <p class="text-body-secondary small mb-0">
                    {{ __('Go to the store and complete the purchase process as usual without any additional steps') }}
                </p>
            </div>

            <div class="col-6 col-lg-3 text-center mb-4 mb-lg-0">
                <div class="site-cashback-orbit site-cashback-orbit-reverse mx-auto mb-3"
                    style="--step-color: var(--Green-Darkest)">
                    <span class="site-cashback-node site-cashback-node-start"></span>
                    <span class="site-cashback-node site-cashback-node-end"></span>

                    <div class="site-cashback-circle">
                        <span class="site-cashback-number">04</span>
                    </div>
                </div>

                <h4 class="mb-2">{{ __('Wait for the cashback in your account') }}</h4>
                <p class="text-body-secondary small mb-0">
                    {{ __('Wait for the cashback amount to appear in your account on :app_name, then you can withdraw it later', ['app_name' => app_name()]) }}
                </p>
            </div>
        </div>
    </div>
</section>
