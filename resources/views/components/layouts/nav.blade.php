<!--! ================================================================ !-->
<!--! [Start] Navigation Manu !-->
<!--! ================================================================ !-->
<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="index.html" class="b-brand">
                <!-- ========   change your logo hear   ============ -->
                <img src="assets/images/logo-full.png" alt="" class="logo logo-lg" />
                <img src="assets/images/logo-abbr.png" alt="" class="logo logo-sm" />
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-item nxl-caption">
                    <label>Navigation</label>
                </li>
                <li class="nxl-item nxl-hasmenu">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboards</span><span class="nxl-arrow"><i
                                class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item"><a class="nxl-link" href="index.html">CRM</a></li>
                        <li class="nxl-item"><a class="nxl-link" href="analytics.html">Analytics</a></li>
                    </ul>
                </li>
                <x-ui.navigation.item-nav title="Patients" icon="feather-users">
                    <x-ui.navigation.link-nav href="{{ route('patient.all') }}">Tous les
                        patients</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="{{ route('patient.create') }}">Nouveau
                        patient</x-ui.navigation.link-nav>
                </x-ui.navigation.item-nav>
                <x-ui.navigation.item-nav title="Consultation" icon="feather-clipboard">
                    <x-ui.navigation.link-nav href="">Triages</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Fiches de Consultations</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Consultations Prénatales</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Calendrier de rendez-vous</x-ui.navigation.link-nav>
                </x-ui.navigation.item-nav>
                <x-ui.navigation.item-nav title="Laboratoire" icon="feather-activity">
                    <x-ui.navigation.link-nav href="">Fiches de Consultations</x-ui.navigation.link-nav>
                </x-ui.navigation.item-nav>
                <x-ui.navigation.item-nav title="Imagerie" icon="feather-camera">
                    <x-ui.navigation.link-nav href="">Fiches de Consultations</x-ui.navigation.link-nav>
                </x-ui.navigation.item-nav>
                <x-ui.navigation.item-nav title="Pharmacie" icon="feather-package">
                    <x-ui.navigation.link-nav href="">Fiches de Consultations</x-ui.navigation.link-nav>
                </x-ui.navigation.item-nav>
                <x-ui.navigation.item-nav title="Facturation et caisse" icon="feather-dollar-sign">
                    <x-ui.navigation.link-nav href="">Facturations</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Grille tarifaire</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Categorisation</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">HealthWallet</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Journal de caisse</x-ui.navigation.link-nav>
                </x-ui.navigation.item-nav>
                <x-ui.navigation.item-nav title="Paramettres" icon="feather-settings">
                    <x-ui.navigation.link-nav href="{{ route('settings.general') }}">Support
                        téchnique</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Groupe d'hopitaux</x-ui.navigation.link-nav>
                    {{-- <x-ui.navigation.link-nav href="">Générale</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Corps médical</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Département</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Catégories</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Projets et campagnes</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Carnet des vaccins</x-ui.navigation.link-nav>
                    <x-ui.navigation.link-nav href="">Assurances</x-ui.navigation.link-nav> --}}
                </x-ui.navigation.item-nav>
            </ul>
            <div class="card text-center">
                <div class="card-body">
                    <i class="feather-sunrise fs-4 text-dark"></i>
                    <h6 class="mt-4 text-dark fw-bolder">Downloading Center</h6>
                    <p class="fs-11 my-3 text-dark">Duralux is a production ready CRM to get started up and running
                        easily.</p>
                    <a href="https://www.themewagon.com/themes/Duralux-admin" target="_blank"
                        class="btn btn-primary text-dark w-100">Download Now</a>
                </div>
            </div>
        </div>
    </div>
</nav>
<!--! ================================================================ !-->
<!--! [End]  Navigation Manu !-->
<!--! ================================================================ !-->
