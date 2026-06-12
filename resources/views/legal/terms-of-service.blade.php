<x-layouts.klaus
    :title="'Terms of Service — '.config('klaus.seo.site_name')"
    description="Terms of Service for Klaus vom Amt. Entertainment and satire only — not legal advice."
    :url="route('legal.terms', absolute: true)"
>
    <x-klaus.legal-page title="Terms of Service">
        <p>
            These Terms of Service ("Terms") govern your use of the Klaus vom Amt website and related
            social media channels (collectively, the "Service"). By accessing or using the Service, you
            agree to these Terms.
        </p>

        <h2 class="text-xl font-black uppercase">1. Entertainment only</h2>
        <p>
            Klaus vom Amt is a satirical comedy project about German bureaucracy. All content — including
            forms, decisions, calculators, and chat replies — is fictional and provided for entertainment
            purposes only. Nothing on this site constitutes legal, tax, immigration, or professional advice.
        </p>

        <h2 class="text-xl font-black uppercase">2. No official affiliation</h2>
        <p>
            Klaus vom Amt is not affiliated with, endorsed by, or connected to any government agency,
            municipality, or public authority in Germany or elsewhere.
        </p>

        <h2 class="text-xl font-black uppercase">3. Acceptable use</h2>
        <p>You agree not to:</p>
        <ul class="list-disc space-y-2 pl-6">
            <li>Use the Service for unlawful purposes or to harass others;</li>
            <li>Attempt to gain unauthorized access to our systems or third-party integrations;</li>
            <li>Scrape, copy, or redistribute content at scale without permission;</li>
            <li>Misrepresent satirical content as official government communication.</li>
        </ul>

        <h2 class="text-xl font-black uppercase">4. Social media and third-party platforms</h2>
        <p>
            We publish content on platforms such as TikTok, Instagram, and YouTube. Your use of those
            platforms is also subject to their respective terms and policies. Where we connect to TikTok
            or other APIs to publish content, we do so only as permitted by those platforms and only for
            our own official accounts.
        </p>

        <h2 class="text-xl font-black uppercase">5. Intellectual property</h2>
        <p>
            The Klaus vom Amt name, character, visuals, text, and other materials are protected by
            applicable intellectual property laws. You may share links to our content and engage with it
            on social platforms in the normal course of use, but you may not reproduce or commercialize
            our materials without prior written permission.
        </p>

        <h2 class="text-xl font-black uppercase">6. Disclaimers</h2>
        <p>
            The Service is provided "as is" and "as available" without warranties of any kind. We do not
            guarantee uninterrupted access, accuracy of satirical content, or that using the site will
            improve your administrative outcomes in real life.
        </p>

        <h2 class="text-xl font-black uppercase">7. Limitation of liability</h2>
        <p>
            To the fullest extent permitted by law, Klaus vom Amt and its operators shall not be liable
            for any indirect, incidental, special, or consequential damages arising from your use of the
            Service.
        </p>

        <h2 class="text-xl font-black uppercase">8. Changes</h2>
        <p>
            We may update these Terms from time to time. The "Last updated" date at the top of this page
            indicates when they were last revised. Continued use of the Service after changes constitutes
            acceptance of the updated Terms.
        </p>

        <h2 class="text-xl font-black uppercase">9. Contact</h2>
        <p>
            Questions about these Terms may be sent to
            <a href="mailto:{{ config('klaus.legal.contact_email') }}" class="font-semibold underline">
                {{ config('klaus.legal.contact_email') }}
            </a>.
        </p>

        <p class="text-sm text-klaus-black/60">
            See also our
            <a href="{{ config('klaus.legal.privacy_url') ?: route('legal.privacy') }}" class="font-semibold underline">
                Privacy Policy
            </a>.
        </p>
    </x-klaus.legal-page>

    <x-klaus.footer />
</x-layouts.klaus>
