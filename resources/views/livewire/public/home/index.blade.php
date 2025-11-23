<div>
    <!-- Hero Section -->
    <section wire:ignore class="relative bg-white dark:bg-zinc-900 flex flex-col items-center justify-center px-4 py-16 md:py-24 overflow-hidden">
        <!-- Decorative Elements -->
        <!-- Speech bubble left -->
        <img src="/assets/images/landing/lefthero-deskt.png" alt="" class="absolute top-16 left-[5%] w-40 md:w-52 hidden md:block" data-aos="fade-up">

        <!-- Star burst -->
        <img src="/assets/images/landing/obj1.png" alt="" class="absolute top-8 left-[18%] w-10 md:w-14" data-aos="fade-up">

        <!-- Squiggly lines top right -->
        <img src="/assets/images/landing/righthero-deskt.png" alt="" class="absolute top-8 right-[5%] w-44 md:w-60 hidden md:block" data-aos="fade-up">

        <!-- Main Content -->
        <div class="text-center z-10">
            <h1 class="text-5xl md:text-7xl font-bold text-[#22c55e] mb-4" data-aos="fade-up">I-Racket</h1>
            <p class="text-lg md:text-xl text-black dark:text-white font-semibold mb-8" data-aos="fade-up">{{ __('landing.tagline') }}</p>

            <!-- App Store Buttons - Black version -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center" data-aos="fade-up">
                <a href="#" class="inline-block hover:scale-105 transition-transform">
                    <img src="/assets/images/landing/badge-appStore.png" alt="Download on App Store" class="h-10">
                </a>
                <a href="#" class="inline-block hover:scale-105 transition-transform">
                    <img src="/assets/images/landing/badge-PlayStore.png" alt="Get it on Google Play" class="h-10">
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section 1 - Green Background -->
    <section wire:ignore class="relative bg-[#22c55e] py-12 md:py-16 px-4 md:px-8 lg:px-16">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <!-- Left Content -->
                <div class="text-white" data-aos="fade-up">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ __('landing.features1_title') }}</h2>
                    <p class="text-base leading-relaxed opacity-90">
                        {{ __('landing.features1_text') }}
                    </p>
                </div>

                <!-- Right Content - Phone Mockup -->
                <div class="relative flex justify-center" data-aos="fade-up">
                    <!-- Decorative pingpong/dashed line -->
                    <img src="/assets/images/landing/pingpong.png" alt="" class="absolute -top-20 left-1/2 -translate-x-1/2 w-40 md:w-52">
                    <img src="/assets/images/landing/iPhone12right2.png" alt="App screenshot" class="w-56 md:w-64 relative z-10 hover:scale-105 transition-transform duration-500">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section 2 - White Background -->
    <section wire:ignore class="relative bg-white dark:bg-zinc-800 py-12 md:py-16 px-4 md:px-8 lg:px-16">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <!-- Left Content - Phone Mockup -->
                <div class="flex justify-center order-2 md:order-1" data-aos="fade-up">
                    <img src="/assets/images/landing/iPhone12left.png" alt="App screenshot" class="w-56 md:w-64 transform -rotate-6 hover:scale-105 transition-transform duration-500">
                </div>

                <!-- Right Content -->
                <div class="order-1 md:order-2" data-aos="fade-up">
                    <div class="flex items-start gap-3 mb-4">
                        <h2 class="text-3xl md:text-4xl font-bold text-black dark:text-white">{{ __('landing.features2_title') }}</h2>
                        <!-- Decorative target icon -->
                        <img src="/assets/images/landing/obj3.png" alt="" class="w-10 md:w-14 flex-shrink-0">
                    </div>
                    <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                        {{ __('landing.features2_text') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section - Dark Gray Background -->
    <section class="relative bg-[#4a4a4a] dark:bg-zinc-800 py-12 md:py-16 px-4 md:px-8 lg:px-16">
        <!-- Decorative squiggle -->
        <img src="/assets/images/landing/obj4.png" alt="" class="absolute top-12 left-[5%] w-12 hidden md:block" data-aos="fade-up">

        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 gap-12">
                <!-- Left - Contact Information -->
                <div wire:ignore class="text-white" data-aos="fade-up">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ __('landing.contact_title') }}</h2>
                    <p class="text-gray-300 mb-8">
                        {{ __('landing.contact_text') }}
                    </p>

                    <!-- Contact Boxes -->
                    <div class="space-y-4">
                        <!-- Email -->
                        <div class="flex items-center gap-4 p-4 bg-[#3a3a3a] dark:bg-zinc-700 rounded-lg hover:bg-[#454545] dark:hover:bg-zinc-600 transition-colors" data-aos="fade-up">
                            <div class="w-10 h-10 bg-[#22c55e] rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">{{ __('landing.contact_email_label') }}</p>
                                <p class="font-medium">info@iracket.se</p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-center gap-4 p-4 bg-[#3a3a3a] dark:bg-zinc-700 rounded-lg hover:bg-[#454545] dark:hover:bg-zinc-600 transition-colors" data-aos="fade-up">
                            <div class="w-10 h-10 bg-[#22c55e] rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">{{ __('landing.contact_phone_label') }}</p>
                                <p class="font-medium">+46 70 123 45 67</p>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="flex items-center gap-4 p-4 bg-[#3a3a3a] dark:bg-zinc-700 rounded-lg hover:bg-[#454545] dark:hover:bg-zinc-600 transition-colors" data-aos="fade-up">
                            <div class="w-10 h-10 bg-[#22c55e] rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">{{ __('landing.contact_address_label') }}</p>
                                <p class="font-medium">Gatuadress 12, 510 20 Göteborg</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right - Contact Form -->
                <div>
                    @if (session()->has('contact_success'))
                        <div class="bg-green-500/20 border border-green-500/30 text-green-100 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ __('landing.contact_success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="bg-red-500/20 border border-red-500/30 text-red-100 px-4 py-3 rounded-lg mb-6">
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium">{{ __('Please fix the following errors:') }}</span>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-8">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form wire:submit="submitContact" class="space-y-4">
                        <input
                            type="text"
                            wire:model="name"
                            name="name"
                            autocomplete="name"
                            placeholder="{{ __('landing.contact_name') }}"
                            class="w-full p-4 bg-white dark:bg-zinc-700 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-[#22c55e] focus:outline-none transition-shadow @error('name') ring-2 ring-red-500 @enderror"
                        >

                        <input
                            type="email"
                            wire:model="email"
                            name="email"
                            autocomplete="email"
                            placeholder="{{ __('landing.contact_email') }}"
                            class="w-full p-4 bg-white dark:bg-zinc-700 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-[#22c55e] focus:outline-none transition-shadow @error('email') ring-2 ring-red-500 @enderror"
                        >

                        <textarea
                            wire:model="message"
                            placeholder="{{ __('landing.contact_message') }}"
                            rows="5"
                            class="w-full p-4 bg-white dark:bg-zinc-700 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none focus:ring-2 focus:ring-[#22c55e] focus:outline-none transition-shadow @error('message') ring-2 ring-red-500 @enderror"
                        ></textarea>

                        <button
                            type="submit"
                            class="w-full sm:w-auto px-12 py-4 bg-[#22c55e] text-white font-semibold rounded-lg hover:bg-[#16a34a] hover:scale-105 transition-all duration-300"
                        >
                            {{ __('landing.contact_submit') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Decorative sparkles -->
        <img src="/assets/images/landing/obj5.png" alt="" class="absolute bottom-12 right-[5%] w-10 hidden md:block" data-aos="fade-up">
    </section>

    <!-- Download CTA Section -->
    <section wire:ignore class="relative bg-white dark:bg-zinc-900 py-12 md:py-16 px-4 md:px-8 lg:px-16">
        <div class="max-w-4xl mx-auto text-center">
            <div class="flex items-center justify-center gap-2 mb-8" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold text-black dark:text-white">{{ __('landing.download_title') }}</h2>
                <!-- Decorative checkmark -->
                <img src="/assets/images/landing/lastsect.png" alt="" class="w-8 md:w-10">
            </div>

            <!-- App Store Buttons - Black version -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center" data-aos="fade-up">
                <a href="#" class="inline-block hover:scale-105 transition-transform">
                    <img src="/assets/images/landing/badge-appStore.png" alt="Download on App Store" class="h-12">
                </a>
                <a href="#" class="inline-block hover:scale-105 transition-transform">
                    <img src="/assets/images/landing/badge-PlayStore.png" alt="Get it on Google Play" class="h-12">
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer wire:ignore class="bg-black py-8 px-4">
        <div class="max-w-6xl mx-auto" data-aos="fade-up">
            <!-- Terms Links -->
            @if($terms->count() > 0)
                <div class="flex flex-wrap justify-center gap-4 mb-4">
                    @foreach($terms as $term)
                        <a href="{{ route('terms.show', $term->slug) }}" class="text-gray-400 hover:text-white text-sm transition-colors">
                            {{ $term->title }}
                        </a>
                        @if(!$loop->last)
                            <span class="text-gray-600">•</span>
                        @endif
                    @endforeach
                </div>
            @endif
            <!-- Copyright -->
            <p class="text-white text-sm text-center">
                {{ __('landing.footer_copyright') }}
            </p>
        </div>
    </footer>
</div>
