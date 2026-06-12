<x-layouts.klaus
    :title="'Privacy Policy — '.config('klaus.seo.site_name')"
    description="Privacy Policy for Klaus vom Amt. How we handle information when you visit our site or connect via TikTok and other platforms."
    :url="route('legal.privacy', absolute: true)"
>
    <x-klaus.legal-page title="Privacy Policy">
        <p>
            This Privacy Policy explains how Klaus vom Amt ("we", "us") collects, uses, and protects
            information when you visit our website or interact with our content on social media platforms
            such as TikTok, Instagram, and YouTube.
        </p>

        <h2 class="text-xl font-black uppercase">1. Who we are</h2>
        <p>
            Klaus vom Amt is a satirical entertainment website and social media presence. The site is
            operated for comedy and educational entertainment about German bureaucracy — not as a
            government service.
        </p>

        <h2 class="text-xl font-black uppercase">2. Information we collect</h2>

        <h3 class="text-lg font-bold">Website visitors</h3>
        <p>
            Our public landing page is primarily informational. We do not require you to create an account
            to browse the site. We may collect standard server and analytics data such as IP address,
            browser type, referring URL, and pages viewed, where such logging is enabled on our hosting
            infrastructure.
        </p>

        <h3 class="text-lg font-bold">Interactive features</h3>
        <p>
            Features such as the chat widget and fake forms run locally in your browser and are not
            designed to store personal submissions on our servers. Do not enter sensitive personal
            information into satirical site features.
        </p>

        <h3 class="text-lg font-bold">Social media platforms</h3>
        <p>
            When you follow, comment on, or otherwise interact with Klaus vom Amt on TikTok or other
            platforms, those platforms may collect and process your data under their own privacy policies.
            We receive only the information those platforms make available to content creators or app
            developers, such as public profile information and engagement metrics.
        </p>

        <h3 class="text-lg font-bold">TikTok API integration</h3>
        <p>
            We use TikTok's developer APIs to publish video content to our official Klaus vom Amt TikTok
            account. This integration uses authorized access tokens stored securely on our servers. We do
            not use TikTok login to collect data from visitors to this website, and we do not sell TikTok
            user data.
        </p>

        <h2 class="text-xl font-black uppercase">3. How we use information</h2>
        <p>We use information only to:</p>
        <ul class="list-disc space-y-2 pl-6">
            <li>Operate, secure, and improve the website;</li>
            <li>Publish and manage content on our official social media accounts;</li>
            <li>Respond to legitimate contact requests;</li>
            <li>Comply with applicable law and platform requirements.</li>
        </ul>

        <h2 class="text-xl font-black uppercase">4. Cookies and local storage</h2>
        <p>
            We may use essential cookies or similar technologies required for basic site functionality,
            such as session management for administrative areas not exposed to the public. We do not use
            third-party advertising cookies on the public landing page.
        </p>

        <h2 class="text-xl font-black uppercase">5. Sharing of information</h2>
        <p>
            We do not sell your personal information. We may share data with service providers that help
            us host the site, publish content, or operate infrastructure, and only to the extent needed
            for those services. We may also disclose information if required by law.
        </p>

        <h2 class="text-xl font-black uppercase">6. Data retention</h2>
        <p>
            We retain information only as long as necessary for the purposes described above, unless a
            longer retention period is required by law or platform policy.
        </p>

        <h2 class="text-xl font-black uppercase">7. Your rights</h2>
        <p>
            Depending on your location, you may have rights to access, correct, delete, or restrict
            processing of your personal data, or to object to certain processing. To exercise these rights,
            contact us at
            <a href="mailto:{{ config('klaus.legal.contact_email') }}" class="font-semibold underline">
                {{ config('klaus.legal.contact_email') }}
            </a>.
        </p>

        <h2 class="text-xl font-black uppercase">8. Children</h2>
        <p>
            The Service is intended for a general audience. We do not knowingly collect personal
            information from children under 13 (or the applicable age in your jurisdiction).
        </p>

        <h2 class="text-xl font-black uppercase">9. International visitors</h2>
        <p>
            If you access the Service from outside the country where our servers are located, your
            information may be transferred to and processed in that country or other jurisdictions where
            our providers operate.
        </p>

        <h2 class="text-xl font-black uppercase">10. Changes to this policy</h2>
        <p>
            We may update this Privacy Policy from time to time. The "Last updated" date at the top
            indicates when it was last revised. Material changes will be reflected on this page.
        </p>

        <h2 class="text-xl font-black uppercase">11. Contact</h2>
        <p>
            Privacy questions may be sent to
            <a href="mailto:{{ config('klaus.legal.contact_email') }}" class="font-semibold underline">
                {{ config('klaus.legal.contact_email') }}
            </a>.
        </p>

        <p class="text-sm text-klaus-black/60">
            See also our
            <a href="{{ config('klaus.legal.terms_url') ?: route('legal.terms') }}" class="font-semibold underline">
                Terms of Service
            </a>.
        </p>
    </x-klaus.legal-page>

    <x-klaus.footer />
</x-layouts.klaus>
