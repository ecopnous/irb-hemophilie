@extends('layouts.app')

@push('styles-vendor')
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/datepicker.min.css">
@endpush

@section('main')
    <x-layouts.main-header title="Patients" :routes="['Nouveau']">
        <button type="submit" form="patientForm" class="btn btn-primary">
            <i class="feather-save me-2"></i>
            <span>Sauvegarder le dossier médical</span>
        </button>
    </x-layouts.main-header>
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-top-0" style="border-radius: 0px">
                    <form id="patientForm" action="{{ route('patient.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="tab-pane fade show active" id="profileTab" role="tabpanel">
                            <div class="card-body personal-info">
                                <div class="mb-4 d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold mb-0 me-4">
                                        <span class="d-block mb-2">Informations personnelles:</span>
                                        <span class="fs-12 fw-normal text-muted text-truncate-1-line">Remplissez les
                                            informations personnelles du patient pour créer sont profil dans le
                                            système.</span>
                                    </h5>
                                </div>
                                <x-forms.item-grid-form title="Profil">
                                    <div class="mb-4 mb-md-0 d-flex gap-4 your-brand">
                                        <div
                                            class="wd-100 ht-100 position-relative overflow-hidden border border-gray-2 rounded">
                                            <img src="assets/images/avatar/1.png" id="previewImage"
                                                class="upload-pic img-fluid rounded h-100 w-100" alt="">
                                            <div
                                                class="position-absolute start-50 top-50 end-0 bottom-0 translate-middle h-100 w-100 hstack align-items-center justify-content-center c-pointer upload-button">
                                                <i class="feather feather-camera" aria-hidden="true"></i>
                                            </div>
                                            <input class="file-upload" type="file" name="photo" accept="image/*"
                                                id="photoInput">
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fs-11 text-gray-500 mt-2"># Télécharger votre photo de profil</div>
                                            <div class="fs-11 text-gray-500"># Taille de l'avatar: 150x150</div>
                                            <div class="fs-11 text-gray-500"># Taille maximale de téléchargement: 2 Mo</div>
                                            <div class="fs-11 text-gray-500"># Formats autorisés: png, jpg, jpeg</div>
                                        </div>
                                    </div>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Noms">
                                    <div class="input-group">
                                        <span class="input-group-text">Noms</span>
                                        <input type="text" name="nom" placeholder="Nom *"
                                            class="form-control @error('nom') is-invalid @enderror"
                                            value="{{ old('nom') }}" required>
                                        <input type="text" name="postnom" placeholder="Post-Nom"
                                            class="form-control @error('postnom') is-invalid @enderror"
                                            value="{{ old('postnom') }}">
                                        <input type="text" name="prenom" placeholder="Prénom *"
                                            class="form-control @error('prenom') is-invalid @enderror"
                                            value="{{ old('prenom') }}" required>
                                    </div>
                                    @error('nom')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                    @error('prenom')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Genre">
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="genre" id="masculin"
                                                value="M" {{ old('genre') === 'M' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="masculin">
                                                Masculin
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="genre" id="feminin"
                                                value="F" {{ old('genre') === 'F' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="feminin">
                                                Féminin
                                            </label>
                                        </div>
                                    </div>
                                    @error('genre')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Etat civil">
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="etat_civil"
                                                id="celibataire" value="Célibataire"
                                                {{ old('etat_civil') === 'Célibataire' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="celibataire">
                                                Célibataire
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="etat_civil"
                                                id="marie" value="Marié"
                                                {{ old('etat_civil') === 'Marié' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="marie">
                                                Marié
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="etat_civil"
                                                id="divorce" value="Divorcé"
                                                {{ old('etat_civil') === 'Divorcé' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="divorce">
                                                Divorcé
                                            </label>
                                        </div>
                                    </div>
                                    @error('etat_civil')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Coordonnées">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-phone"></i></div>
                                        <input type="tel" name="telephone"
                                            class="form-control @error('telephone') is-invalid @enderror"
                                            placeholder="Téléphone" value="{{ old('telephone') }}">
                                        <div class="input-group-text"><i class="feather-mail"></i></div>
                                        <input type="email" name="email"
                                            class="form-control @error('email') is-invalid @enderror" placeholder="Email"
                                            value="{{ old('email') }}">
                                    </div>
                                    @error('telephone')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                    @error('email')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Date de naissance">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-calendar"></i></div>
                                        <input type="date" name="date_naissance"
                                            class="form-control @error('date_naissance') is-invalid @enderror"
                                            value="{{ old('date_naissance') }}" required>
                                    </div>
                                    @error('date_naissance')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </x-forms.item-grid-form>
                            </div>

                            <hr class="my-0">
                            <div class="card-body additional-info">
                                <div class="mb-4 d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold mb-0 me-4">
                                        <span class="d-block mb-2">Informations régionales:</span>
                                        <span class="fs-12 fw-normal text-muted text-truncate-1-line">Complétez les
                                            informations de localisation pour améliorer nos services.</span>
                                    </h5>
                                </div>
                                <x-forms.item-grid-form title="Nationalité">
                                    <select class="form-control" data-select2-selector="country">
                                        <option data-country="af">Afghanistan</option>
                                        <option data-country="ax">Åland Islands</option>
                                        <option data-country="al">Albania</option>
                                        <option data-country="dz">Algeria</option>
                                        <option data-country="as">American Samoa</option>
                                        <option data-country="ad">Andorra</option>
                                        <option data-country="ao">Angola</option>
                                        <option data-country="ai">Anguilla</option>
                                        <option data-country="aq">Antarctica</option>
                                        <option data-country="ag">Antigua & Barbuda</option>
                                        <option data-country="ar">Argentina</option>
                                        <option data-country="am">Armenia</option>
                                        <option data-country="aw">Aruba</option>
                                        <option data-country="au">Australia</option>
                                        <option data-country="at">Austria</option>
                                        <option data-country="az">Azerbaijan</option>
                                        <option data-country="bs">Bahamas</option>
                                        <option data-country="bh">Bahrain</option>
                                        <option data-country="bd">Bangladesh</option>
                                        <option data-country="bb">Barbados</option>
                                        <option data-country="by">Belarus</option>
                                        <option data-country="be">Belgium</option>
                                        <option data-country="bz">Belize</option>
                                        <option data-country="bj">Benin</option>
                                        <option data-country="bm">Bermuda</option>
                                        <option data-country="bt">Bhutan</option>
                                        <option data-country="bo">Bolivia</option>
                                        <option data-country="bq">Caribbean Netherlands</option>
                                        <option data-country="ba">Bosnia & Herzegovina</option>
                                        <option data-country="bw">Botswana</option>
                                        <option data-country="bv">Bouvet Island</option>
                                        <option data-country="br">Brazil</option>
                                        <option data-country="io">British Indian Ocean Territory</option>
                                        <option data-country="bn">Brunei</option>
                                        <option data-country="bg">Bulgaria</option>
                                        <option data-country="bf">Burkina Faso</option>
                                        <option data-country="bi">Burundi</option>
                                        <option data-country="kh">Cambodia</option>
                                        <option data-country="cm">Cameroon</option>
                                        <option data-country="ca">Canada</option>
                                        <option data-country="cv">Cape Verde</option>
                                        <option data-country="ky">Cayman Islands</option>
                                        <option data-country="cf">Central African Republic</option>
                                        <option data-country="td">Chad</option>
                                        <option data-country="cl">Chile</option>
                                        <option data-country="cn">China</option>
                                        <option data-country="cx">Christmas Island</option>
                                        <option data-country="cc">Cocos (Keeling) Islands</option>
                                        <option data-country="co">Colombia</option>
                                        <option data-country="km">Comoros</option>
                                        <option data-country="cg">Congo - Brazzaville</option>
                                        <option data-country="cd" selected>Congo - Kinshasa</option>
                                        <option data-country="ck">Cook Islands</option>
                                        <option data-country="cr">Costa Rica</option>
                                        <option data-country="ci">Côte d'Ivoire</option>
                                        <option data-country="hr">Croatia</option>
                                        <option data-country="cu">Cuba</option>
                                        <option data-country="cu">Curaçao</option>
                                        <option data-country="cy">Cyprus</option>
                                        <option data-country="cz">Czechia</option>
                                        <option data-country="dk">Denmark</option>
                                        <option data-country="dj">Djibouti</option>
                                        <option data-country="dm">Dominica</option>
                                        <option data-country="do">Dominican Republic</option>
                                        <option data-country="ec">Ecuador</option>
                                        <option data-country="eg">Egypt</option>
                                        <option data-country="sv">El Salvador</option>
                                        <option data-country="gq">Equatorial Guinea</option>
                                        <option data-country="er">Eritrea</option>
                                        <option data-country="ee">Estonia</option>
                                        <option data-country="et">Ethiopia</option>
                                        <option data-country="fk">Falkland Islands (Islas Malvinas)</option>
                                        <option data-country="fo">Faroe Islands</option>
                                        <option data-country="fj">Fiji</option>
                                        <option data-country="fi">Finland</option>
                                        <option data-country="fr">France</option>
                                        <option data-country="gf">French Guiana</option>
                                        <option data-country="pf">French Polynesia</option>
                                        <option data-country="tf">French Southern Territories</option>
                                        <option data-country="ga">Gabon</option>
                                        <option data-country="gm">Gambia</option>
                                        <option data-country="ge">Georgia</option>
                                        <option data-country="de">Germany</option>
                                        <option data-country="gh">Ghana</option>
                                        <option data-country="gi">Gibraltar</option>
                                        <option data-country="gr">Greece</option>
                                        <option data-country="gl">Greenland</option>
                                        <option data-country="gd">Grenada</option>
                                        <option data-country="gp">Guadeloupe</option>
                                        <option data-country="gu">Guam</option>
                                        <option data-country="gt">Guatemala</option>
                                        <option data-country="gg">Guernsey</option>
                                        <option data-country="gn">Guinea</option>
                                        <option data-country="gw">Guinea-Bissau</option>
                                        <option data-country="gy">Guyana</option>
                                        <option data-country="ht">Haiti</option>
                                        <option data-country="hm">Heard & McDonald Islands</option>
                                        <option data-country="va">Vatican City</option>
                                        <option data-country="hn">Honduras</option>
                                        <option data-country="hk">Hong Kong</option>
                                        <option data-country="hu">Hungary</option>
                                        <option data-country="is">Iceland</option>
                                        <option data-country="in">India</option>
                                        <option data-country="id">Indonesia</option>
                                        <option data-country="ir">Iran</option>
                                        <option data-country="iq">Iraq</option>
                                        <option data-country="ie">Ireland</option>
                                        <option data-country="im">Isle of Man</option>
                                        <option data-country="il">Israel</option>
                                        <option data-country="it">Italy</option>
                                        <option data-country="jm">Jamaica</option>
                                        <option data-country="jp">Japan</option>
                                        <option data-country="je">Jersey</option>
                                        <option data-country="jo">Jordan</option>
                                        <option data-country="kz">Kazakhstan</option>
                                        <option data-country="ke">Kenya</option>
                                        <option data-country="ki">Kiribati</option>
                                        <option data-country="kp">North Korea</option>
                                        <option data-country="kr">South Korea</option>
                                        <option data-country="xk">Kosovo</option>
                                        <option data-country="kw">Kuwait</option>
                                        <option data-country="kg">Kyrgyzstan</option>
                                        <option data-country="la">Laos</option>
                                        <option data-country="lv">Latvia</option>
                                        <option data-country="lb">Lebanon</option>
                                        <option data-country="ls">Lesotho</option>
                                        <option data-country="lr">Liberia</option>
                                        <option data-country="ly">Libya</option>
                                        <option data-country="li">Liechtenstein</option>
                                        <option data-country="lt">Lithuania</option>
                                        <option data-country="lu">Luxembourg</option>
                                        <option data-country="mo">Macao</option>
                                        <option data-country="mk">North Macedonia</option>
                                        <option data-country="mg">Madagascar</option>
                                        <option data-country="mw">Malawi</option>
                                        <option data-country="my">Malaysia</option>
                                        <option data-country="mv">Maldives</option>
                                        <option data-country="ml">Mali</option>
                                        <option data-country="mt">Malta</option>
                                        <option data-country="mh">Marshall Islands</option>
                                        <option data-country="mq">Martinique</option>
                                        <option data-country="mr">Mauritania</option>
                                        <option data-country="mu">Mauritius</option>
                                        <option data-country="yt">Mayotte</option>
                                        <option data-country="mx">Mexico</option>
                                        <option data-country="fm">Micronesia</option>
                                        <option data-country="md">Moldova</option>
                                        <option data-country="mc">Monaco</option>
                                        <option data-country="mn">Mongolia</option>
                                        <option data-country="me">Montenegro</option>
                                        <option data-country="ms">Montserrat</option>
                                        <option data-country="ma">Morocco</option>
                                        <option data-country="mz">Mozambique</option>
                                        <option data-country="mm">Myanmar (Burma)</option>
                                        <option data-country="na">Namibia</option>
                                        <option data-country="nr">Nauru</option>
                                        <option data-country="np">Nepal</option>
                                        <option data-country="nl">Netherlands</option>
                                        <option data-country="cu">Curaçao</option>
                                        <option data-country="nc">New Caledonia</option>
                                        <option data-country="nz">New Zealand</option>
                                        <option data-country="ni">Nicaragua</option>
                                        <option data-country="ne">Niger</option>
                                        <option data-country="ng">Nigeria</option>
                                        <option data-country="nu">Niue</option>
                                        <option data-country="nf">Norfolk Island</option>
                                        <option data-country="mp">Northern Mariana Islands</option>
                                        <option data-country="no">Norway</option>
                                        <option data-country="om">Oman</option>
                                        <option data-country="pk">Pakistan</option>
                                        <option data-country="pw">Palau</option>
                                        <option data-country="ps">Palestine</option>
                                        <option data-country="pa">Panama</option>
                                        <option data-country="pg">Papua New Guinea</option>
                                        <option data-country="py">Paraguay</option>
                                        <option data-country="pe">Peru</option>
                                        <option data-country="ph">Philippines</option>
                                        <option data-country="pn">Pitcairn Islands</option>
                                        <option data-country="pl">Poland</option>
                                        <option data-country="pt">Portugal</option>
                                        <option data-country="pr">Puerto Rico</option>
                                        <option data-country="qa">Qatar</option>
                                        <option data-country="re">Réunion</option>
                                        <option data-country="ro">Romania</option>
                                        <option data-country="ru">Russia</option>
                                        <option data-country="rw">Rwanda</option>
                                        <option data-country="bl">St. Barthélemy</option>
                                        <option data-country="sh">St. Helena</option>
                                        <option data-country="kn">St. Kitts & Nevis</option>
                                        <option data-country="lc">St. Lucia</option>
                                        <option data-country="mf">St. Martin</option>
                                        <option data-country="pm">St. Pierre & Miquelon</option>
                                        <option data-country="vc">St. Vincent & Grenadines</option>
                                        <option data-country="ws">Samoa</option>
                                        <option data-country="sm">San Marino</option>
                                        <option data-country="st">São Tomé & Príncipe</option>
                                        <option data-country="sa">Saudi Arabia</option>
                                        <option data-country="sn">Senegal</option>
                                        <option data-country="rs">Serbia</option>
                                        <option data-country="sr">Serbia</option>
                                        <option data-country="sc">Seychelles</option>
                                        <option data-country="sl">Sierra Leone</option>
                                        <option data-country="sg">Singapore</option>
                                        <option data-country="sx">Sint Maarten</option>
                                        <option data-country="sk">Slovakia</option>
                                        <option data-country="si">Slovenia</option>
                                        <option data-country="sb">Solomon Islands</option>
                                        <option data-country="so">Somalia</option>
                                        <option data-country="za">South Africa</option>
                                        <option data-country="gs">South Georgia & South Sandwich Islands</option>
                                        <option data-country="ss">South Sudan</option>
                                        <option data-country="es">Spain</option>
                                        <option data-country="lk">Sri Lanka</option>
                                        <option data-country="sd">Sudan</option>
                                        <option data-country="sr">Suriname</option>
                                        <option data-country="sj">Svalbard & Jan Mayen</option>
                                        <option data-country="sz">Eswatini</option>
                                        <option data-country="se">Sweden</option>
                                        <option data-country="ch">Switzerland</option>
                                        <option data-country="sy">Syria</option>
                                        <option data-country="tw">Taiwan</option>
                                        <option data-country="tj">Tajikistan</option>
                                        <option data-country="tz">Tanzania</option>
                                        <option data-country="th">Thailand</option>
                                        <option data-country="tl">Timor-Leste</option>
                                        <option data-country="tg">Togo</option>
                                        <option data-country="tk">Tokelau</option>
                                        <option data-country="to">Tonga</option>
                                        <option data-country="tt">Trinidad & Tobago</option>
                                        <option data-country="tn">Tunisia</option>
                                        <option data-country="tr">Turkey</option>
                                        <option data-country="tm">Turkmenistan</option>
                                        <option data-country="tc">Turks & Caicos Islands</option>
                                        <option data-country="tv">Tuvalu</option>
                                        <option data-country="ug">Uganda</option>
                                        <option data-country="ua">Ukraine</option>
                                        <option data-country="ae">United Arab Emirates</option>
                                        <option data-country="gb">United Kingdom</option>
                                        <option data-country="us">United States</option>
                                        <option data-country="um">U.S. Outlying Islands</option>
                                        <option data-country="uy">Uruguay</option>
                                        <option data-country="uz">Uzbekistan</option>
                                        <option data-country="vu">Vanuatu</option>
                                        <option data-country="ve">Venezuela</option>
                                        <option data-country="vn">Vietnam</option>
                                        <option data-country="vg">British Virgin Islands</option>
                                        <option data-country="vi">U.S. Virgin Islands</option>
                                        <option data-country="wf">Wallis & Futuna</option>
                                        <option data-country="eh">Western Sahara</option>
                                        <option data-country="ye">Yemen</option>
                                        <option data-country="zm">Zambia</option>
                                        <option data-country="zw">Zimbabwe</option>
                                    </select>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Province">
                                    <select class="form-control" id="provinceSelect" data-select2-selector="city">
                                        <option value="">-- Sélectionner une province --</option>
                                    </select>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Territoire/Ville">
                                    <select class="form-control" id="territoireSelect" data-select2-selector="city"
                                        disabled>
                                        <option value="">-- Sélectionner un territoire/ville --</option>
                                    </select></x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Commune">
                                    <select class="form-control" id="communeSelect" data-select2-selector="city"
                                        disabled>
                                        <option value="">-- Sélectionner une commune --</option>
                                    </select>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Quartier/N°">
                                    <div class="input-group">
                                        <span class="input-group-text">Q:</span>
                                        <input type="text" aria-label="postnom" placeholder="Quartier"
                                            class="form-control">
                                        <span class="input-group-text">N°</span>
                                        <input type="text" aria-label="prenom" placeholder="N° d'habitation *"
                                            class="form-control">
                                    </div>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Langues parlées">
                                    <select class="form-control" data-select2-selector="language" multiple>
                                        <option data-language="bg-primary">Afrikaans</option>
                                        <option data-language="bg-warning">Albanian - shqip</option>
                                        <option data-language="bg-cyan">Amharic - አማርኛ</option>
                                        <option data-language="bg-green">Arabic - العربية</option>
                                        <option data-language="bg-black">Aragonese - aragonés</option>
                                        <option data-language="bg-teal">Armenian - հայերեն</option>
                                        <option data-language="bg-success">Asturian - asturianu</option>
                                        <option data-language="bg-cyan">Azerbaijani - azərbaycan dili</option>
                                        <option data-language="bg-indigo">Basque - euskara</option>
                                        <option data-language="bg-teal">Belarusian - беларуская</option>
                                        <option data-language="bg-black">Bengali - বাংলা</option>
                                        <option data-language="bg-green">Bosnian - bosanski</option>
                                        <option data-language="bg-primary">Breton - brezhoneg</option>
                                        <option data-language="bg-warning">Bulgarian - български</option>
                                        <option data-language="bg-teal">Catalan - català</option>
                                        <option data-language="bg-black">Central Kurdish - کوردی (دەستنوسی عەرەبی)</option>
                                        <option data-language="bg-green">Chinese - 中文</option>
                                        <option data-language="bg-cyan">Chinese (Hong Kong) - 中文（香港）</option>
                                        <option data-language="bg-primary">Chinese (Simplified) - 中文（简体）</option>
                                        <option data-language="bg-danger">Chinese (Traditional) - 中文（繁體）</option>
                                        <option data-language="bg-cyan">Corsican</option>
                                        <option data-language="bg-black">Croatian - hrvatski</option>
                                        <option data-language="bg-warning">Czech - čeština</option>
                                        <option data-language="bg-primary">Danish - dansk</option>
                                        <option data-language="bg-teal">Dutch - Nederlands</option>
                                        <option data-language="bg-danger">English</option>
                                        <option data-language="bg-green">English (Australia)</option>
                                        <option data-language="bg-black">English (Canada)</option>
                                        <option data-language="bg-cyan">English (India)</option>
                                        <option data-language="bg-primary">English (New Zealand)</option>
                                        <option data-language="bg-indigo">English (South Africa)</option>
                                        <option data-language="bg-black">English (United Kingdom)</option>
                                        <option data-language="bg-teal">English (United States)</option>
                                        <option data-language="bg-green">Esperanto - esperanto</option>
                                        <option data-language="bg-cyan">Estonian - eesti</option>
                                        <option data-language="bg-primary">Faroese - føroyskt</option>
                                        <option data-language="bg-black">Filipino</option>
                                        <option data-language="bg-cyan">Finnish - suomi</option>
                                        <option data-language="bg-primary" selected>French - français</option>
                                        <option data-language="bg-success">French (Canada) - français (Canada)</option>
                                        <option data-language="bg-warning">French (France) - français (France)</option>
                                        <option data-language="bg-black">French (Switzerland) - français (Suisse)</option>
                                        <option data-language="bg-primary">Galician - galego</option>
                                        <option data-language="bg-teal">Georgian - ქართული</option>
                                        <option data-language="bg-black">German - Deutsch</option>
                                        <option data-language="bg-green">German (Austria) - Deutsch (Österreich)</option>
                                        <option data-language="bg-danger">German (Germany) - Deutsch (Deutschland)</option>
                                        <option data-language="bg-indigo">German (Liechtenstein) - Deutsch (Liechtenstein)
                                        </option>
                                        <option data-language="bg-cyan">German (Switzerland) - Deutsch (Schweiz)</option>
                                        <option data-language="bg-primary">Greek - Ελληνικά</option>
                                        <option data-language="bg-green">Guarani</option>
                                        <option data-language="bg-teal">Gujarati - ગુજરાતી</option>
                                        <option data-language="bg-success">Hausa</option>
                                        <option data-language="bg-primary">Hawaiian - ʻŌlelo Hawaiʻi</option>
                                        <option data-language="bg-cyan">Hebrew - עברית</option>
                                        <option data-language="bg-warning">Hindi - हिन्दी</option>
                                        <option data-language="bg-green">Hungarian - magyar</option>
                                        <option data-language="bg-black">Icelandic - íslenska</option>
                                        <option data-language="bg-danger">Indonesian - Indonesia</option>
                                        <option data-language="bg-primary">Interlingua</option>
                                        <option data-language="bg-green">Irish - Gaeilge</option>
                                        <option data-language="bg-success">Italian - italiano</option>
                                        <option data-language="bg-cyan">Italian (Italy) - italiano (Italia)</option>
                                        <option data-language="bg-teal">Italian (Switzerland) - italiano (Svizzera)
                                        </option>
                                        <option data-language="bg-indigo">Japanese - 日本語</option>
                                        <option data-language="bg-primary">Kannada - ಕನ್ನಡ</option>
                                        <option data-language="bg-cyan">Kazakh - қазақ тілі</option>
                                        <option data-language="bg-black">Khmer - ខ្មែរ</option>
                                        <option data-language="bg-primary">Korean - 한국어</option>
                                        <option data-language="bg-warning">Kurdish - Kurdî</option>
                                        <option data-language="bg-cyan">Kyrgyz - кыргызча</option>
                                        <option data-language="bg-danger">Lao - ລາວ</option>
                                        <option data-language="bg-primary">Latin</option>
                                        <option data-language="bg-orange">Latvian - latviešu</option>
                                        <option data-language="bg-green" selected>Lingala - lingála</option>
                                        <option data-language="bg-black">Lithuanian - lietuvių</option>
                                        <option data-language="bg-primary">Macedonian - македонски</option>
                                        <option data-language="bg-indigo">Malay - Bahasa Melayu</option>
                                        <option data-language="bg-green">Malayalam - മലയാളം</option>
                                        <option data-language="bg-cyan">Maltese - Malti</option>
                                        <option data-language="bg-teal">Marathi - मराठी</option>
                                        <option data-language="bg-primary">Mongolian - монгол</option>
                                        <option data-language="bg-danger">Nepali - नेपाली</option>
                                        <option data-language="bg-green">Norwegian - norsk</option>
                                        <option data-language="bg-warning">Norwegian Bokmål - norsk bokmål</option>
                                        <option data-language="bg-primary">Norwegian Nynorsk - nynorsk</option>
                                        <option data-language="bg-success">Occitan</option>
                                        <option data-language="bg-cyan">Oriya - ଓଡ଼ିଆ</option>
                                        <option data-language="bg-black">Oromo - Oromoo</option>
                                        <option data-language="bg-danger">Pashto - پښتو</option>
                                        <option data-language="bg-green">Persian - فارسی</option>
                                        <option data-language="bg-primary">Polish - polski</option>
                                        <option data-language="bg-teal">Portuguese - português</option>
                                        <option data-language="bg-danger">Portuguese (Brazil) - português (Brasil)</option>
                                        <option data-language="bg-black">Portuguese (Portugal) - português (Portugal)
                                        </option>
                                        <option data-language="bg-green">Punjabi - ਪੰਜਾਬੀ</option>
                                        <option data-language="bg-indigo">Quechua</option>
                                        <option data-language="bg-success">Romanian - română</option>
                                        <option data-language="bg-warning">Romanian (Moldova) - română (Moldova)</option>
                                        <option data-language="bg-primary">Romansh - rumantsch</option>
                                        <option data-language="bg-danger">Russian - русский</option>
                                        <option data-language="bg-green">Scottish Gaelic</option>
                                        <option data-language="bg-orange">Serbian - српски</option>
                                        <option data-language="bg-teal">Serbo - Croatian</option>
                                        <option data-language="bg-primary">Shona - chiShona</option>
                                        <option data-language="bg-cyan">Sindhi</option>
                                        <option data-language="bg-black">Sinhala - සිංහල</option>
                                        <option data-language="bg-warning">Slovak - slovenčina</option>
                                        <option data-language="bg-danger">Slovenian - slovenščina</option>
                                        <option data-language="bg-green">Somali - Soomaali</option>
                                        <option data-language="bg-primary">Southern Sotho</option>
                                        <option data-language="bg-orange">Spanish - español</option>
                                        <option data-language="bg-indigo">Spanish (Argentina) - español (Argentina)
                                        </option>
                                        <option data-language="bg-green">Spanish (Latin America) - español (Latinoamérica)
                                        </option>
                                        <option data-language="bg-cyan">Spanish (Mexico) - español (México)</option>
                                        <option data-language="bg-black">Spanish (Spain) - español (España)</option>
                                        <option data-language="bg-success">Spanish (United States) - español (Estados
                                            Unidos)
                                        </option>
                                        <option data-language="bg-primary">Sundanese</option>
                                        <option data-language="bg-teal">Swahili - Kiswahili</option>
                                        <option data-language="bg-green">Swedish - svenska</option>
                                        <option data-language="bg-cyan">Tajik - тоҷикӣ</option>
                                        <option data-language="bg-warning">Tamil - தமிழ்</option>
                                        <option data-language="bg-primary">Tatar</option>
                                        <option data-language="bg-success">Telugu - తెలుగు</option>
                                        <option data-language="bg-black">Thai - ไทย</option>
                                        <option data-language="bg-green">Tigrinya - ትግርኛ</option>
                                        <option data-language="bg-teal">Tongan - lea fakatonga</option>
                                        <option data-language="bg-primary">Turkish - Türkçe</option>
                                        <option data-language="bg-danger">Turkmen</option>
                                        <option data-language="bg-indigo">Twi</option>
                                        <option data-language="bg-black">Ukrainian - українська</option>
                                        <option data-language="bg-green">Urdu - اردو</option>
                                        <option data-language="bg-cyan">Uyghur</option>
                                        <option data-language="bg-primary">Uzbek - o‘zbek</option>
                                        <option data-language="bg-success">Vietnamese - Tiếng Việt</option>
                                        <option data-language="bg-cyan">Walloon - wa</option>
                                        <option data-language="bg-primary">Welsh - Cymraeg</option>
                                        <option data-language="bg-teal">Western Frisian</option>
                                        <option data-language="bg-warning">Xhosa</option>
                                        <option data-language="bg-indigo">Yiddish</option>
                                        <option data-language="bg-green">Yoruba - Èdè Yorùbá</option>
                                        <option data-language="bg-black">Zulu - isiZulu</option>
                                    </select>
                                </x-forms.item-grid-form>

                            </div>
                            <hr class="my-0">
                            <div class="card-body additional-info">
                                <div class="mb-4 d-flex align-items-center justify-content-between">
                                    <h5 class="fw-bold mb-0 me-4">
                                        <span class="d-block mb-2">Autres Informations:</span>
                                        <span class="fs-12 fw-normal text-muted text-truncate-1-line">Complétez les
                                            informations de localisation et de communication pour améliorer nos
                                            services.</span>
                                    </h5>
                                </div>

                                <x-forms.item-grid-form title="Type du dossier">
                                    <select class="form-control" data-select2-selector="city">
                                        <option data-city="bg-primary">Patient standard</option>
                                    </select>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Categorisation">
                                    <select class="form-control" data-select2-selector="city">
                                        <option data-city="bg-primary">Categorie A</option>
                                    </select>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Prise en charge">
                                    <select class="form-control" data-select2-selector="city">
                                        <option data-city="bg-primary">Personnel</option>
                                        <option data-city="bg-secondary">Aleutians East Borough</option>
                                    </select>
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Identifiant Numéro en Santé - INS">
                                    <input type="text" class="form-control" placeholder="INS">
                                </x-forms.item-grid-form>
                                <x-forms.item-grid-form title="Note rapide">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-type"></i></div>
                                        <textarea class="form-control" id="aboutInput" cols="30" rows="5" placeholder="Note"></textarea>
                                    </div>
                                </x-forms.item-grid-form>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts-vendors')
    <script src="assets/vendors/js/select2.min.js"></script>
    <script src="assets/vendors/js/select2-active.min.js"></script>
    <script src="assets/vendors/js/datepicker.min.js"></script>
    <script src="assets/vendors/js/lslstrength.min.js"></script>
@endpush

@push('scripts-page')
    <script src="assets/js/customers-create-init.min.js"></script>
    <script>
        // Données des provinces, territoires et communes de la RDC
        const dataRDC = {
            "pays": "République Démocratique du Congo",
            "provinces": [{
                    "nom": "Kinshasa",
                    "chef_lieu": "Kinshasa",
                    "villes": [{
                        "nom": "Kinshasa",
                        "communes": ["Bandalungwa", "Barumbu", "Bumbu", "Gombe", "Kalamu", "Kasa-Vubu",
                            "Kimbanseke", "Kinsenso", "Kinshasa", "Kintambo", "Kisenso", "Lemba",
                            "Limete", "Lingwala", "Makala", "Maloùku", "Masina", "Matete",
                            "Mont-Ngafula", "Ngaba", "Ngaliema", "Ngiri-Ngiri", "Nsele", "Selembao"
                        ]
                    }],
                    "territoires": []
                },
                {
                    "nom": "Haut-Katanga",
                    "chef_lieu": "Lubumbashi",
                    "villes": [{
                            "nom": "Lubumbashi",
                            "communes": ["Annexe", "Kamalondo", "Kampemba", "Katuba", "Kenya", "Lubumbashi",
                                "Rwashi"
                            ]
                        },
                        {
                            "nom": "Likasi",
                            "communes": ["Kikula", "Panda", "Shituru", "Likasi"]
                        }
                    ],
                    "territoires": ["Kambove", "Kasenga", "Kipushi", "Mitwaba", "Pweto", "Sakania"]
                },
                {
                    "nom": "Nord-Kivu",
                    "chef_lieu": "Goma",
                    "villes": [{
                            "nom": "Goma",
                            "communes": ["Goma", "Karisimbi"]
                        },
                        {
                            "nom": "Butembo",
                            "communes": ["Bulengera", "Kimbulu", "Mususa", "Vutshana"]
                        },
                        {
                            "nom": "Beni",
                            "communes": ["Bungulu", "Beu", "Ruwenzori", "Muhekera"]
                        }
                    ],
                    "territoires": ["Beni", "Lubero", "Masisi", "Nyiragongo", "Rutshuru", "Walikale"]
                },
                {
                    "nom": "Kongo Central",
                    "chef_lieu": "Matadi",
                    "villes": [{
                            "nom": "Matadi",
                            "communes": ["Matadi", "Nzanza", "Mvuzi"]
                        },
                        {
                            "nom": "Boma",
                            "communes": ["Kabondo", "Kalamu", "Nzadi"]
                        }
                    ],
                    "territoires": ["Kasangulu", "Kimvula", "Lukula", "Luozi", "Madimba", "Mbanza-Ngungu", "Moanda",
                        "Sekebanza", "Songololo", "Tshela"
                    ]
                },
                {
                    "nom": "Sud-Kivu",
                    "chef_lieu": "Bukavu",
                    "villes": [{
                            "nom": "Bukavu",
                            "communes": ["Bagira", "Ibanda", "Kadutu"]
                        },
                        {
                            "nom": "Uvira",
                            "communes": ["Kalundu", "Kamvivira", "Mulongwe"]
                        }
                    ],
                    "territoires": ["Fizi", "Idjwi", "Kabare", "Kalehe", "Mwenga", "Shabunda", "Uvira", "Walungu"]
                },
                {
                    "nom": "Tshopo",
                    "chef_lieu": "Kisangani",
                    "villes": [{
                        "nom": "Kisangani",
                        "communes": ["Makiso", "Tshopo", "Mangobo", "Kabondo", "Kisangani", "Lubunga"]
                    }],
                    "territoires": ["Bafwasende", "Banalia", "Basoko", "Isangi", "Opala", "Ubundu", "Yahuma"]
                }
            ]
        };

        // Initialisation des sélects
        const provinceSelect = document.getElementById('provinceSelect');
        const territoireSelect = document.getElementById('territoireSelect');
        const communeSelect = document.getElementById('communeSelect');

        // Remplir les provinces
        dataRDC.provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.nom;
            option.textContent = province.nom;
            provinceSelect.appendChild(option);
        });

        // Initialiser Select2 sur les trois sélects
        $(provinceSelect).select2();
        $(territoireSelect).select2();
        $(communeSelect).select2();

        // Événement de changement de province
        $(provinceSelect).on('change', function() {
            const selectedValue = $(this).val();
            const selectedProvince = dataRDC.provinces.find(p => p.nom === selectedValue);

            // Réinitialiser les sélects enfants
            $(territoireSelect).prop('disabled', false).html(
                '<option value="">-- Sélectionner un territoire/ville --</option>');
            $(communeSelect).prop('disabled', true).html(
                '<option value="">-- Sélectionner une commune --</option>');

            if (selectedProvince) {
                // Ajouter les villes
                selectedProvince.villes.forEach(ville => {
                    const option = document.createElement('option');
                    option.value = ville.nom;
                    option.textContent = ville.nom + ' (Ville)';
                    option.dataset.type = 'ville';
                    option.dataset.communes = JSON.stringify(ville.communes);
                    territoireSelect.appendChild(option);
                });

                // Ajouter les territoires
                selectedProvince.territoires.forEach(territoire => {
                    const option = document.createElement('option');
                    option.value = territoire;
                    option.textContent = territoire + ' (Territoire)';
                    option.dataset.type = 'territoire';
                    territoireSelect.appendChild(option);
                });

                // Rafraîchir Select2
                $(territoireSelect).prop('disabled', false).trigger('change.select2');
            }
        });

        // Événement de changement de territoire/ville
        $(territoireSelect).on('change', function() {
            const selectedValue = $(this).val();
            $(communeSelect).html('<option value="">-- Sélectionner une commune --</option>');

            if (selectedValue) {
                const selectedOption = this.options[this.selectedIndex];
                const communes = selectedOption.dataset.communes ? JSON.parse(selectedOption.dataset.communes) : [];

                if (communes.length > 0) {
                    communes.forEach(commune => {
                        const option = document.createElement('option');
                        option.value = commune;
                        option.textContent = commune;
                        communeSelect.appendChild(option);
                    });
                    $(communeSelect).prop('disabled', false).trigger('change.select2');
                } else {
                    $(communeSelect).prop('disabled', true).trigger('change.select2');
                }
            } else {
                $(communeSelect).prop('disabled', true).trigger('change.select2');
            }
        });
    </script>
@endpush
