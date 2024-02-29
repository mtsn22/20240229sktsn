<?php

namespace App\Filament\Tsn\Resources\PSB;

use App\Filament\Tsn\Resources\PSB\PendaftarNaikQismResource\Pages;
use App\Filament\Tsn\Resources\PSB\PendaftarNaikQismResource\RelationManagers;
use App\Filament\Tsn\Resources\PSB\PendaftarNaikQismResource\Widgets\ListPendaftarNaikQism;
use App\Filament\Tsn\Widgets\PSB\ListPendaftar;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Kelas;
use App\Models\KelasSantri;
use App\Models\Kelurahan;
use App\Models\Kodepos;
use App\Models\Pendaftar;
use App\Models\Provinsi;
use App\Models\Qism;
use App\Models\QismDetail;
use App\Models\QismDetailHasKelas;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Santri;
use App\Models\Semester;
use App\Models\StatusSantri;
use App\Models\TahunAjaran;
use App\Models\Walisantri;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\ImageEntry;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use stdClass;

class PendaftarNaikQismResource extends Resource
{

    public static function canViewAny(): bool
    {
        return auth()->user()->mudirqism !== null;
    }

    protected static ?string $navigationGroup = 'PSB';

    protected static ?int $navigationSort = 01010;

    protected static ?string $modelLabel = 'Pendaftar Naik Qism';

    protected static ?string $navigationLabel = 'Pendaftar Naik Qism';

    protected static ?string $pluralModelLabel = 'Pendaftar Naik Qism';

    protected static ?string $model = Santri::class;

    protected static ?string $navigationIcon = 'heroicon-s-user-plus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Tabs::make('Tabs')
                    ->tabs([


                        Tabs\Tab::make('Walisantri')
                            ->schema([

                                Group::make()
                                    ->relationship('walisantri')
                                    ->schema([

                                        Section::make('Informasi Pendaftar')
                                            ->collapsed()
                                            ->compact()
                                            ->schema([
                                                Grid::make()
                                                    ->schema([
                                                        TextInput::make('kartu_keluarga_santri')
                                                            ->label('Nomor Kartu Keluarga')
                                                            ->disabled()
                                                            ->live(),

                                                        TextInput::make('nama_kpl_kel_santri')
                                                            ->label('Nama Kepala Keluarga')
                                                            ->disabled()
                                                            ->live(),

                                                        TextInput::make('hp_komunikasi')
                                                            ->label('No Handphone walisantri untuk komunikasi')
                                                            ->helperText('Contoh: 82187782223')
                                                            // ->mask('82187782223')
                                                            ->prefix('62')
                                                            ->tel()
                                                            ->live()
                                                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                                            ->required()

                                                    ]),



                                            ])->compact(),
                                        // ->collapsed(fn (Get $get): bool => $get('is_collapse')),

                                        //AYAH KANDUNG
                                        Section::make('Walisantri')
                                            ->collapsed()
                                            ->compact()
                                            ->schema([

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b"><p class="text-lg strong"><strong>A. AYAH KANDUNG</strong></p></div>')),

                                                Radio::make('ak_nama_lengkap_sama')
                                                    ->label('Apakah Nama sama dengan Nama Kepala Keluarga?')
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    // ->hidden(fn (Get $get) =>
                                                    // $get('ak_status') !== 'Masih Hidup')
                                                    ->afterStateUpdated(function (Get $get, Set $set) {

                                                        if ($get('ak_nama_lengkap_sama') === 'Ya') {
                                                            $set('ak_nama_lengkap', $get('nama_kpl_kel_santri'));
                                                            $set('ik_nama_lengkap_sama', 'Tidak');
                                                            $set('ik_nama_lengkap', null);
                                                            $set('w_nama_lengkap_sama', 'Tidak');
                                                            $set('w_nama_lengkap', null);
                                                        } else {
                                                            $set('ak_nama_lengkap', null);
                                                        }
                                                    })->columnSpanFull(),

                                                TextInput::make('ak_nama_lengkap')
                                                    ->label('Nama Lengkap')
                                                    ->hint('Isi sesuai dengan KK')
                                                    ->hintColor('danger')
                                                    ->required()
                                                    // ->disabled(fn (Get $get) =>
                                                    // $get('ak_nama_lengkap_sama') === 'Ya')
                                                    ->dehydrated(),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>A.01 STATUS AYAH KANDUNG</strong></p>
                                           </div>')),

                                                Select::make('ak_status')
                                                    ->label('Status')
                                                    ->placeholder('Pilih Status')
                                                    ->options([
                                                        'Masih Hidup' => 'Masih Hidup',
                                                        'Sudah Meninggal' => 'Sudah Meninggal',
                                                        'Tidak Diketahui' => 'Tidak Diketahui',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false),

                                                TextInput::make('ak_nama_kunyah')
                                                    ->label('Nama Hijroh/Islami')
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                Select::make('ak_kewarganegaraan')
                                                    ->label('Kewarganegaraan')
                                                    ->placeholder('Pilih Kewarganegaraan')
                                                    ->options([
                                                        'WNI' => 'WNI',
                                                        'WNA' => 'WNA',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                TextInput::make('ak_nik')
                                                    ->label('NIK')
                                                    ->hint('Isi sesuai dengan KK')
                                                    ->hintColor('danger')
                                                    ->length(16)
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_kewarganegaraan') !== 'WNI' ||
                                                        $get('ak_status') !== 'Masih Hidup'),

                                                Grid::make(2)
                                                    ->schema([

                                                        TextInput::make('ak_asal_negara')
                                                            ->label('Asal Negara')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_kewarganegaraan') !== 'WNA' ||
                                                                $get('ak_status') !== 'Masih Hidup'),

                                                        TextInput::make('ak_kitas')
                                                            ->label('KITAS')
                                                            ->hint('Nomor Izin Tinggal (KITAS)')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_kewarganegaraan') !== 'WNA' ||
                                                                $get('ak_status') !== 'Masih Hidup'),
                                                    ]),
                                                Grid::make(2)
                                                    ->schema([

                                                        TextInput::make('ak_tempat_lahir')
                                                            ->label('Tempat Lahir')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),

                                                        DatePicker::make('ak_tanggal_lahir')
                                                            ->label('Tanggal Lahir')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            // ->format('dd/mm/yyyy')
                                                            ->displayFormat('d M Y')
                                                            ->native(false)
                                                            ->closeOnDateSelection()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),
                                                    ]),

                                                Grid::make(3)
                                                    ->schema([

                                                        Select::make('ak_pend_terakhir')
                                                            ->label('Pendidikan Terakhir')
                                                            ->placeholder('Pilih Pendidikan Terakhir')
                                                            ->options([
                                                                'SD/Sederajat' => 'SD/Sederajat',
                                                                'SMP/Sederajat' => 'SMP/Sederajat',
                                                                'SMA/Sederajat' => 'SMA/Sederajat',
                                                                'D1' => 'D1',
                                                                'D2' => 'D2',
                                                                'D3' => 'D3',
                                                                'D4/S1' => 'D4/S1',
                                                                'S2' => 'S2',
                                                                'S3' => 'S3',
                                                                'Tidak Bersekolah' => 'Tidak Bersekolah',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),

                                                        Select::make('ak_pekerjaan_utama')
                                                            ->label('Pekerjaan Utama')
                                                            ->placeholder('Pilih Pekerjaan Utama')
                                                            ->options([
                                                                'Tidak Bekerja' => 'Tidak Bekerja',
                                                                'Pensiunan' => 'Pensiunan',
                                                                'PNS' => 'PNS',
                                                                'TNI/Polisi' => 'TNI/Polisi',
                                                                'Guru/Dosen' => 'Guru/Dosen',
                                                                'Pegawai Swasta' => 'Pegawai Swasta',
                                                                'Wiraswasta' => 'Wiraswasta',
                                                                'Pengacara/Jaksa/Hakim/Notaris' => 'Pengacara/Jaksa/Hakim/Notaris',
                                                                'Seniman/Pelukis/Artis/Sejenis' => 'Seniman/Pelukis/Artis/Sejenis',
                                                                'Dokter/Bidan/Perawat' => 'Dokter/Bidan/Perawat',
                                                                'Pilot/Pramugara' => 'Pilot/Pramugara',
                                                                'Pedagang' => 'Pedagang',
                                                                'Petani/Peternak' => 'Petani/Peternak',
                                                                'Nelayan' => 'Nelayan',
                                                                'Buruh (Tani/Pabrik/Bangunan)' => 'Buruh (Tani/Pabrik/Bangunan)',
                                                                'Sopir/Masinis/Kondektur' => 'Sopir/Masinis/Kondektur',
                                                                'Politikus' => 'Politikus',
                                                                'Lainnya' => 'Lainnya',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),

                                                        Select::make('ak_pghsln_rt')
                                                            ->label('Penghasilan Rata-Rata')
                                                            ->placeholder('Pilih Penghasilan Rata-Rata')
                                                            ->options([
                                                                'Kurang dari 500.000' => 'Kurang dari 500.000',
                                                                '500.000 - 1.000.000' => '500.000 - 1.000.000',
                                                                '1.000.001 - 2.000.000' => '1.000.001 - 2.000.000',
                                                                '2.000.001 - 3.000.000' => '2.000.001 - 3.000.000',
                                                                '3.000.001 - 5.000.000' => '3.000.001 - 5.000.000',
                                                                'Lebih dari 5.000.000' => 'Lebih dari 5.000.000',
                                                                'Tidak ada' => 'Tidak ada',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),
                                                    ]),

                                                Grid::make(1)
                                                    ->schema([

                                                        Radio::make('ak_tdk_hp')
                                                            ->label('Memiliki nomor handphone?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),

                                                        Radio::make('ak_nomor_handphone_sama')
                                                            ->label('Apakah nomor handphone sama dengan Pendaftar?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_tdk_hp') !== 'Ya' ||
                                                                $get('ak_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Get $get, Set $set) {

                                                                if ($get('ak_nomor_handphone_sama') === 'Ya') {
                                                                    $set('ak_nomor_handphone', $get('hp_komunikasi'));
                                                                    $set('ik_nomor_handphone_sama', 'Tidak');
                                                                    $set('ik_nomor_handphone', null);
                                                                    $set('w_nomor_handphone_sama', 'Tidak');
                                                                    $set('w_nomor_handphone', null);
                                                                } else {
                                                                    $set('ak_nomor_handphone', null);
                                                                }
                                                            })->columnSpanFull(),

                                                        TextInput::make('ak_nomor_handphone')
                                                            ->label('No. Handphone')
                                                            ->helperText('Contoh: 82187782223')
                                                            // ->mask('82187782223')
                                                            ->prefix('62')
                                                            ->tel()
                                                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('ak_nomor_handphone_sama') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_tdk_hp') !== 'Ya' ||
                                                                $get('ak_status') !== 'Masih Hidup'),
                                                    ]),

                                                // KARTU KELUARGA AYAH KANDUNG
                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                                <p class="text-lg strong"><strong>A.02 KARTU KELUARGA</strong></p>
                                                <p class="text-lg strong"><strong>AYAH KANDUNG</strong></p>
                                            </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                Grid::make(2)
                                                    ->schema([

                                                        Radio::make('ak_kk_sama_pendaftar')
                                                            ->label('Apakah KK dan Nama Kepala Keluarga sama dengan Pendaftar?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Get $get, Set $set) {

                                                                if ($get('ak_kk_sama_pendaftar') === 'Ya') {
                                                                    $set('ak_no_kk', $get('kartu_keluarga_santri'));
                                                                    $set('ak_kep_kel_kk', $get('nama_kpl_kel_santri'));
                                                                    $set('ik_kk_sama_pendaftar', 'Tidak');
                                                                    $set('ik_no_kk', null);
                                                                    $set('ik_kep_kel_kk', null);
                                                                    $set('w_kk_sama_pendaftar', 'Tidak');
                                                                    $set('w_no_kk', null);
                                                                    $set('w_kep_kel_kk', null);
                                                                } else {
                                                                    $set('ak_no_kk', null);
                                                                    $set('ak_kep_kel_kk', null);
                                                                }
                                                            })->columnSpanFull(),

                                                        TextInput::make('ak_no_kk')
                                                            ->label('No. KK Ayah Kandung')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->length(16)
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('ak_kk_sama_pendaftar') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),

                                                        TextInput::make('ak_kep_kel_kk')
                                                            ->label('Nama Kepala Keluarga')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('ak_kk_sama_pendaftar') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_status') !== 'Masih Hidup'),
                                                    ]),


                                                // ALAMAT AYAH KANDUNG
                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>A.03 TEMPAT TINGGAL DOMISILI</strong></p>
                                        <p class="text-lg strong"><strong>AYAH KANDUNG</strong></p>
                                           </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                Radio::make('al_ak_tgldi_ln')
                                                    ->label('Apakah tinggal di luar negeri?')
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                Textarea::make('al_ak_almt_ln')
                                                    ->label('Alamat Luar Negeri')
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('al_ak_tgldi_ln') !== 'Ya'),

                                                Select::make('al_ak_stts_rmh')
                                                    ->label('Status Kepemilikan Rumah')
                                                    ->placeholder('Pilih Status Kepemilikan Rumah')
                                                    ->options([
                                                        'Milik Sendiri' => 'Milik Sendiri',
                                                        'Rumah Orang Tua' => 'Rumah Orang Tua',
                                                        'Rumah Saudara/kerabat' => 'Rumah Saudara/kerabat',
                                                        'Rumah Dinas' => 'Rumah Dinas',
                                                        'Sewa/kontrak' => 'Sewa/kontrak',
                                                        'Lainnya' => 'Lainnya',
                                                    ])
                                                    ->searchable()
                                                    ->required()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                        $get('ak_status') !== 'Masih Hidup'),

                                                Grid::make(2)
                                                    ->schema([

                                                        Select::make('al_ak_provinsi_id')
                                                            ->label('Provinsi')
                                                            ->placeholder('Pilih Provinsi')
                                                            ->options(Provinsi::all()->pluck('provinsi', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Set $set) {
                                                                $set('al_ak_kabupaten_id', null);
                                                                $set('al_ak_kecamatan_id', null);
                                                                $set('al_ak_kelurahan_id', null);
                                                                $set('al_ak_kodepos', null);
                                                            }),

                                                        Select::make('al_ak_kabupaten_id')
                                                            ->label('Kabupaten')
                                                            ->placeholder('Pilih Kabupaten')
                                                            ->options(fn (Get $get): Collection => Kabupaten::query()
                                                                ->where('provinsi_id', $get('al_ak_provinsi_id'))
                                                                ->pluck('kabupaten', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup'),

                                                        Select::make('al_ak_kecamatan_id')
                                                            ->label('Kecamatan')
                                                            ->placeholder('Pilih Kecamatan')
                                                            ->options(fn (Get $get): Collection => Kecamatan::query()
                                                                ->where('kabupaten_id', $get('al_ak_kabupaten_id'))
                                                                ->pluck('kecamatan', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup'),

                                                        Select::make('al_ak_kelurahan_id')
                                                            ->label('Kelurahan')
                                                            ->placeholder('Pilih Kelurahan')
                                                            ->options(fn (Get $get): Collection => Kelurahan::query()
                                                                ->where('kecamatan_id', $get('al_ak_kecamatan_id'))
                                                                ->pluck('kelurahan', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Get $get, ?string $state, Set $set, ?string $old) {

                                                                if (($get('al_ak_kodepos') ?? '') !== Str::slug($old)) {
                                                                    return;
                                                                }

                                                                $kodepos = Kodepos::where('kelurahan_id', $state)->get('kodepos');

                                                                $state = $kodepos;

                                                                foreach ($state as $state) {
                                                                    $set('al_ak_kodepos', Str::substr($state, 12, 5));
                                                                }
                                                            }),


                                                        TextInput::make('al_ak_rt')
                                                            ->label('RT')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup'),

                                                        TextInput::make('al_ak_rw')
                                                            ->label('RW')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup'),

                                                        Textarea::make('al_ak_alamat')
                                                            ->label('Alamat')
                                                            ->required()
                                                            ->columnSpanFull()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup'),

                                                        TextInput::make('al_ak_kodepos')
                                                            ->label('Kodepos')
                                                            ->disabled()
                                                            ->required()
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_ak_tgldi_ln') !== 'Tidak' ||
                                                                $get('ak_status') !== 'Masih Hidup'),
                                                    ]),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                            <p class="text-lg strong"><strong>Kajian yang diikuti</strong></p>
                                            </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                Textarea::make('ak_ustadz_kajian')
                                                    ->label('Ustadz yang mengisi kajian')
                                                    ->required()
                                                    // ->default('4232')
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),

                                                TextArea::make('ak_tempat_kajian')
                                                    ->label('Tempat kajian yang diikuti')
                                                    ->required()
                                                    // ->default('4232')
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_status') !== 'Masih Hidup'),





                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b"></div>')),


                                                // //IBU KANDUNG
                                                // Section::make('')
                                                //     ->schema([

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div></div>')),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>B. IBU KANDUNG</strong></p>
                                    </div>')),

                                                Radio::make('ik_nama_lengkap_sama')
                                                    ->label('Apakah Nama sama dengan Nama Kepala Keluarga?')
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ak_nama_lengkap_sama') !== 'Tidak')
                                                    ->afterStateUpdated(function (Get $get, Set $set) {

                                                        if ($get('ik_nama_lengkap_sama') === 'Ya') {
                                                            $set('ik_nama_lengkap', $get('nama_kpl_kel_santri'));
                                                            $set('w_nama_lengkap_sama', 'Tidak');
                                                            $set('w_nama_lengkap', null);
                                                        } else {
                                                            $set('ik_nama_lengkap', null);
                                                        }
                                                    })->columnSpanFull(),

                                                TextInput::make('ik_nama_lengkap')
                                                    ->label('Nama Lengkap')
                                                    ->hint('Isi sesuai dengan KK')
                                                    ->hintColor('danger')
                                                    ->required()
                                                    // ->disabled(fn (Get $get) =>
                                                    // $get('ik_nama_lengkap_sama') === 'Ya')
                                                    ->dehydrated(),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>B.01 STATUS IBU KANDUNG</strong></p>
                                    </div>')),

                                                Select::make('ik_status')
                                                    ->label('Status')
                                                    ->placeholder('Pilih Status')
                                                    ->options([
                                                        'Masih Hidup' => 'Masih Hidup',
                                                        'Sudah Meninggal' => 'Sudah Meninggal',
                                                        'Tidak Diketahui' => 'Tidak Diketahui',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false),

                                                TextInput::make('ik_nama_kunyah')
                                                    ->label('Nama Hijroh/Islami')
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                Select::make('ik_kewarganegaraan')
                                                    ->label('Kewarganegaraan')
                                                    ->placeholder('Pilih Kewarganegaraan')
                                                    ->options([
                                                        'WNI' => 'WNI',
                                                        'WNA' => 'WNA',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                TextInput::make('ik_nik')
                                                    ->label('NIK')
                                                    ->hint('Isi sesuai dengan KK')
                                                    ->hintColor('danger')
                                                    ->length(16)
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_kewarganegaraan') !== 'WNI' ||
                                                        $get('ik_status') !== 'Masih Hidup'),

                                                Grid::make(2)
                                                    ->schema([

                                                        TextInput::make('ik_asal_negara')
                                                            ->label('Asal Negara')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kewarganegaraan') !== 'WNA' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        TextInput::make('ik_kitas')
                                                            ->label('KITAS')
                                                            ->hint('Nomor Izin Tinggal (KITAS)')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kewarganegaraan') !== 'WNA' ||
                                                                $get('ik_status') !== 'Masih Hidup'),
                                                    ]),
                                                Grid::make(2)
                                                    ->schema([

                                                        TextInput::make('ik_tempat_lahir')
                                                            ->label('Tempat Lahir')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_status') !== 'Masih Hidup'),

                                                        DatePicker::make('ik_tanggal_lahir')
                                                            ->label('Tanggal Lahir')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            // ->format('dd/mm/yyyy')
                                                            ->displayFormat('d M Y')
                                                            ->native(false)
                                                            ->closeOnDateSelection()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_status') !== 'Masih Hidup'),
                                                    ]),

                                                Grid::make(3)
                                                    ->schema([

                                                        Select::make('ik_pend_terakhir')
                                                            ->label('Pendidikan Terakhir')
                                                            ->placeholder('Pilih Pendidikan Terakhir')
                                                            ->options([
                                                                'SD/Sederajat' => 'SD/Sederajat',
                                                                'SMP/Sederajat' => 'SMP/Sederajat',
                                                                'SMA/Sederajat' => 'SMA/Sederajat',
                                                                'D1' => 'D1',
                                                                'D2' => 'D2',
                                                                'D3' => 'D3',
                                                                'D4/S1' => 'D4/S1',
                                                                'S2' => 'S2',
                                                                'S3' => 'S3',
                                                                'Tidak Bersekolah' => 'Tidak Bersekolah',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_status') !== 'Masih Hidup'),

                                                        Select::make('ik_pekerjaan_utama')
                                                            ->label('Pekerjaan Utama')
                                                            ->placeholder('Pilih Pekerjaan Utama')
                                                            ->options([
                                                                'Tidak Bekerja' => 'Tidak Bekerja',
                                                                'Pensiunan' => 'Pensiunan',
                                                                'PNS' => 'PNS',
                                                                'TNI/Polisi' => 'TNI/Polisi',
                                                                'Guru/Dosen' => 'Guru/Dosen',
                                                                'Pegawai Swasta' => 'Pegawai Swasta',
                                                                'Wiraswasta' => 'Wiraswasta',
                                                                'Pengacara/Jaksa/Hakim/Notaris' => 'Pengacara/Jaksa/Hakim/Notaris',
                                                                'Seniman/Pelukis/Artis/Sejenis' => 'Seniman/Pelukis/Artis/Sejenis',
                                                                'Dokter/Bidan/Perawat' => 'Dokter/Bidan/Perawat',
                                                                'Pilot/Pramugara' => 'Pilot/Pramugara',
                                                                'Pedagang' => 'Pedagang',
                                                                'Petani/Peternak' => 'Petani/Peternak',
                                                                'Nelayan' => 'Nelayan',
                                                                'Buruh (Tani/Pabrik/Bangunan)' => 'Buruh (Tani/Pabrik/Bangunan)',
                                                                'Sopir/Masinis/Kondektur' => 'Sopir/Masinis/Kondektur',
                                                                'Politikus' => 'Politikus',
                                                                'Lainnya' => 'Lainnya',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_status') !== 'Masih Hidup'),

                                                        Select::make('ik_pghsln_rt')
                                                            ->label('Penghasilan Rata-Rata')
                                                            ->placeholder('Pilih Penghasilan Rata-Rata')
                                                            ->options([
                                                                'Kurang dari 500.000' => 'Kurang dari 500.000',
                                                                '500.000 - 1.000.000' => '500.000 - 1.000.000',
                                                                '1.000.001 - 2.000.000' => '1.000.001 - 2.000.000',
                                                                '2.000.001 - 3.000.000' => '2.000.001 - 3.000.000',
                                                                '3.000.001 - 5.000.000' => '3.000.001 - 5.000.000',
                                                                'Lebih dari 5.000.000' => 'Lebih dari 5.000.000',
                                                                'Tidak ada' => 'Tidak ada',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_status') !== 'Masih Hidup'),
                                                    ]),

                                                Grid::make(1)
                                                    ->schema([

                                                        Radio::make('ik_tdk_hp')
                                                            ->label('Memiliki nomor handphone?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_status') !== 'Masih Hidup'),

                                                        Radio::make('ik_nomor_handphone_sama')
                                                            ->label('Apakah nomor handphone sama dengan Pendaftar?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_tdk_hp') !== 'Ya' ||
                                                                $get('ak_nomor_handphone_sama') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Get $get, Set $set) {

                                                                if ($get('ik_nomor_handphone_sama') === 'Ya') {
                                                                    $set('ik_nomor_handphone', $get('hp_komunikasi'));
                                                                    $set('w_nomor_handphone_sama', 'Tidak');
                                                                    $set('w_nomor_handphone', null);
                                                                } else {
                                                                    $set('ik_nomor_handphone', null);
                                                                }
                                                            })->columnSpanFull(),

                                                        TextInput::make('ik_nomor_handphone')
                                                            ->label('No. Handphone')
                                                            ->helperText('Contoh: 82187782223')
                                                            // ->mask('82187782223')
                                                            ->prefix('62')
                                                            ->tel()
                                                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('ik_nomor_handphone_sama') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_tdk_hp') !== 'Ya' ||
                                                                $get('ik_status') !== 'Masih Hidup'),
                                                    ]),

                                                // KARTU KELUARGA IBU KANDUNG
                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                    <p class="text-lg strong"><strong>B.02 KARTU KELUARGA</strong></p>
                                    <p class="text-lg strong"><strong>IBU KANDUNG</strong></p>
                                    </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                Radio::make('ik_kk_sama_ak')
                                                    ->label('Apakah KK Ibu Kandung sama dengan KK Ayah Kandung?')
                                                    ->live()
                                                    ->options(function (Get $get) {

                                                        if ($get('ak_status') !== 'Masih Hidup') {

                                                            return ([
                                                                'Tidak' => 'Tidak',
                                                            ]);
                                                        } else {
                                                            return ([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ]);
                                                        }
                                                    })
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $sama = $get('ik_kk_sama_ak');
                                                        $set('al_ik_sama_ak', $sama);

                                                        if ($get('ik_kk_sama_ak') === 'Ya') {
                                                            $set('ik_kk_sama_pendaftar', 'Tidak');
                                                        }
                                                    })
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                Radio::make('al_ik_sama_ak')
                                                    ->label('Alamat sama dengan Ayah Kandung')
                                                    ->helperText('Untuk mengubah alamat, silakan mengubah status KK Ibu kandung')
                                                    ->disabled()
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                Grid::make(2)
                                                    ->schema([

                                                        Radio::make('ik_kk_sama_pendaftar')
                                                            ->label('Apakah KK dan Nama Kepala Keluarga sama dengan Pendaftar?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('ak_kk_sama_pendaftar') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Get $get, Set $set) {

                                                                if ($get('ik_kk_sama_pendaftar') === 'Ya') {
                                                                    $set('ik_no_kk', $get('kartu_keluarga_santri'));
                                                                    $set('ik_kep_kel_kk', $get('nama_kpl_kel_santri'));
                                                                    $set('w_kk_sama_pendaftar', 'Tidak');
                                                                    $set('w_no_kk', null);
                                                                    $set('w_kep_kel_kk', null);
                                                                } else {
                                                                    $set('ik_no_kk', null);
                                                                    $set('ik_kep_kel_kk', null);
                                                                }
                                                            })->columnSpanFull(),

                                                        TextInput::make('ik_no_kk')
                                                            ->label('No. KK Ibu Kandung')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->length(16)
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('ik_kk_sama_pendaftar') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        TextInput::make('ik_kep_kel_kk')
                                                            ->label('Nama Kepala Keluarga')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('ik_kk_sama_pendaftar') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),
                                                    ]),


                                                // ALAMAT AYAH KANDUNG
                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>B.03 TEMPAT TINGGAL DOMISILI</strong></p>
                                        <p class="text-lg strong"><strong>IBU KANDUNG</strong></p>
                                    </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                        $get('ik_status') !== 'Masih Hidup'),

                                                Radio::make('al_ik_tgldi_ln')
                                                    ->label('Apakah tinggal di luar negeri?')
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                        $get('ik_status') !== 'Masih Hidup'),

                                                Textarea::make('al_ik_almt_ln')
                                                    ->label('Alamat Luar Negeri')
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                        $get('al_ik_tgldi_ln') !== 'Ya' ||
                                                        $get('ik_status') !== 'Masih Hidup'),

                                                Select::make('al_ik_stts_rmh')
                                                    ->label('Status Kepemilikan Rumah')
                                                    ->placeholder('Pilih Status Kepemilikan Rumah')
                                                    ->options([
                                                        'Milik Sendiri' => 'Milik Sendiri',
                                                        'Rumah Orang Tua' => 'Rumah Orang Tua',
                                                        'Rumah Saudara/kerabat' => 'Rumah Saudara/kerabat',
                                                        'Rumah Dinas' => 'Rumah Dinas',
                                                        'Sewa/kontrak' => 'Sewa/kontrak',
                                                        'Lainnya' => 'Lainnya',
                                                    ])
                                                    ->searchable()
                                                    ->required()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                        $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                        $get('ik_status') !== 'Masih Hidup'),

                                                Grid::make(2)
                                                    ->schema([

                                                        Select::make('al_ik_provinsi_id')
                                                            ->label('Provinsi')
                                                            ->placeholder('Pilih Provinsi')
                                                            ->options(Provinsi::all()->pluck('provinsi', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Set $set) {
                                                                $set('al_ik_kabupaten_id', null);
                                                                $set('al_ik_kecamatan_id', null);
                                                                $set('al_ik_kelurahan_id', null);
                                                                $set('al_ik_kodepos', null);
                                                            }),

                                                        Select::make('al_ik_kabupaten_id')
                                                            ->label('Kabupaten')
                                                            ->placeholder('Pilih Kabupaten')
                                                            ->options(fn (Get $get): Collection => Kabupaten::query()
                                                                ->where('provinsi_id', $get('al_ik_provinsi_id'))
                                                                ->pluck('kabupaten', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        Select::make('al_ik_kecamatan_id')
                                                            ->label('Kecamatan')
                                                            ->placeholder('Pilih Kecamatan')
                                                            ->options(fn (Get $get): Collection => Kecamatan::query()
                                                                ->where('kabupaten_id', $get('al_ik_kabupaten_id'))
                                                                ->pluck('kecamatan', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        Select::make('al_ik_kelurahan_id')
                                                            ->label('Kelurahan')
                                                            ->placeholder('Pilih Kelurahan')
                                                            ->options(fn (Get $get): Collection => Kelurahan::query()
                                                                ->where('kecamatan_id', $get('al_ik_kecamatan_id'))
                                                                ->pluck('kelurahan', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup')
                                                            ->afterStateUpdated(function (Get $get, ?string $state, Set $set, ?string $old) {

                                                                if (($get('al_ik_kodepos') ?? '') !== Str::slug($old)) {
                                                                    return;
                                                                }

                                                                $kodepos = Kodepos::where('kelurahan_id', $state)->get('kodepos');

                                                                $state = $kodepos;

                                                                foreach ($state as $state) {
                                                                    $set('al_ik_kodepos', Str::substr($state, 12, 5));
                                                                }
                                                            }),


                                                        TextInput::make('al_ik_rt')
                                                            ->label('RT')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        TextInput::make('al_ik_rw')
                                                            ->label('RW')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        Textarea::make('al_ik_alamat')
                                                            ->label('Alamat')
                                                            ->required()
                                                            ->columnSpanFull()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),

                                                        TextInput::make('al_ik_kodepos')
                                                            ->label('Kodepos')
                                                            ->disabled()
                                                            ->required()
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ik_kk_sama_ak') !== 'Tidak' ||
                                                                $get('al_ik_tgldi_ln') !== 'Tidak' ||
                                                                $get('ik_status') !== 'Masih Hidup'),
                                                    ]),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                             <p class="text-lg strong"><strong>Kajian yang diikuti</strong></p>
                                          </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                Textarea::make('ik_ustadz_kajian')
                                                    ->label('Ustadz yang mengisi kajian')
                                                    ->required()
                                                    // ->default('4232')
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),

                                                TextArea::make('ik_tempat_kajian')
                                                    ->label('Tempat kajian yang diikuti')
                                                    ->required()
                                                    // ->default('4232')
                                                    ->hidden(fn (Get $get) =>
                                                    $get('ik_status') !== 'Masih Hidup'),



                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b border-tsn-accent">
                                    </div>')),


                                                // //IBU KANDUNG
                                                // Section::make('')
                                                //     ->schema([

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div>
                                    </div>')),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>C. WALI</strong></p>
                                    </div>')),

                                                Select::make('w_status')
                                                    ->label('Status')
                                                    ->placeholder('Pilih Status')
                                                    ->options(function (Get $get) {

                                                        if (($get('ak_status') == "Masih Hidup" && $get('ik_status') == "Masih Hidup")) {
                                                            return ([
                                                                'Sama dengan ayah kandung' => 'Sama dengan ayah kandung',
                                                                'Sama dengan ibu kandung' => 'Sama dengan ibu kandung',
                                                                'Lainnya' => 'Lainnya'
                                                            ]);
                                                        } elseif (($get('ak_status') == "Masih Hidup" && $get('ik_status') !== "Masih Hidup")) {
                                                            return ([
                                                                'Sama dengan ayah kandung' => 'Sama dengan ayah kandung',
                                                                'Lainnya' => 'Lainnya'
                                                            ]);
                                                        } elseif (($get('ak_status') !== "Masih Hidup" && $get('ik_status') == "Masih Hidup")) {
                                                            return ([
                                                                'Sama dengan ibu kandung' => 'Sama dengan ibu kandung',
                                                                'Lainnya' => 'Lainnya'
                                                            ]);
                                                        } elseif (($get('ak_status') !== "Masih Hidup" && $get('ik_status') !== "Masih Hidup")) {
                                                            return ([
                                                                'Lainnya' => 'Lainnya'
                                                            ]);
                                                        }
                                                    })
                                                    ->required()
                                                    ->live()
                                                    ->native(false),

                                                Select::make('w_hubungan')
                                                    ->label('Hubungan wali dengan calon santri')
                                                    ->placeholder('Pilih Hubungan')
                                                    ->options([
                                                        'Kakek/Nenek' => 'Kakek/Nenek',
                                                        'Paman/Bibi' => 'Paman/Bibi',
                                                        'Kakak' => 'Kakak',
                                                        'Lainnya' => 'Lainnya',
                                                    ])
                                                    ->required()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Radio::make('w_nama_lengkap_sama')
                                                    ->label('Apakah Nama sama dengan Nama Kepala Keluarga?')
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya' ||
                                                        $get('ak_nama_lengkap_sama') !== 'Tidak' ||
                                                        $get('ik_nama_lengkap_sama') !== 'Tidak')
                                                    ->afterStateUpdated(function (Get $get, Set $set) {

                                                        if ($get('w_nama_lengkap_sama') === 'Ya') {
                                                            $set('w_nama_lengkap', $get('nama_kpl_kel_santri'));
                                                        } else {
                                                            $set('w_nama_lengkap', null);
                                                        }
                                                    })->columnSpanFull(),

                                                TextInput::make('w_nama_lengkap')
                                                    ->label('Nama Lengkap')
                                                    ->hint('Isi sesuai dengan KK')
                                                    ->hintColor('danger')
                                                    ->required()
                                                    // ->disabled(fn (Get $get) =>
                                                    // $get('w_nama_lengkap_sama') === 'Ya')
                                                    ->dehydrated()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>C.01 STATUS WALI</strong></p>
                                    </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                TextInput::make('w_nama_kunyah')
                                                    ->label('Nama Hijroh/Islami')
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Select::make('w_kewarganegaraan')
                                                    ->label('Kewarganegaraan')
                                                    ->placeholder('Pilih Kewarganegaraan')
                                                    ->options([
                                                        'WNI' => 'WNI',
                                                        'WNA' => 'WNA',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                TextInput::make('w_nik')
                                                    ->label('NIK')
                                                    ->hint('Isi sesuai dengan KK')
                                                    ->hintColor('danger')
                                                    ->length(16)
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_kewarganegaraan') !== 'WNI' ||
                                                        $get('w_status') !== 'Lainnya'),

                                                Grid::make(2)
                                                    ->schema([

                                                        TextInput::make('w_asal_negara')
                                                            ->label('Asal Negara')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_kewarganegaraan') !== 'WNA' ||
                                                                $get('w_status') !== 'Lainnya'),

                                                        TextInput::make('w_kitas')
                                                            ->label('KITAS')
                                                            ->hint('Nomor Izin Tinggal (KITAS)')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_kewarganegaraan') !== 'WNA' ||
                                                                $get('w_status') !== 'Lainnya'),
                                                    ]),
                                                Grid::make(2)
                                                    ->schema([

                                                        TextInput::make('w_tempat_lahir')
                                                            ->label('Tempat Lahir')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),

                                                        DatePicker::make('w_tanggal_lahir')
                                                            ->label('Tanggal Lahir')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            // ->format('dd/mm/yyyy')
                                                            ->displayFormat('d M Y')
                                                            ->native(false)
                                                            ->closeOnDateSelection()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),
                                                    ]),

                                                Grid::make(3)
                                                    ->schema([

                                                        Select::make('w_pend_terakhir')
                                                            ->label('Pendidikan Terakhir')
                                                            ->placeholder('Pilih Pendidikan Terakhir')
                                                            ->options([
                                                                'SD/Sederajat' => 'SD/Sederajat',
                                                                'SMP/Sederajat' => 'SMP/Sederajat',
                                                                'SMA/Sederajat' => 'SMA/Sederajat',
                                                                'D1' => 'D1',
                                                                'D2' => 'D2',
                                                                'D3' => 'D3',
                                                                'D4/S1' => 'D4/S1',
                                                                'S2' => 'S2',
                                                                'S3' => 'S3',
                                                                'Tidak Bersekolah' => 'Tidak Bersekolah',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),

                                                        Select::make('w_pekerjaan_utama')
                                                            ->label('Pekerjaan Utama')
                                                            ->placeholder('Pilih Pekerjaan Utama')
                                                            ->options([
                                                                'Tidak Bekerja' => 'Tidak Bekerja',
                                                                'Pensiunan' => 'Pensiunan',
                                                                'PNS' => 'PNS',
                                                                'TNI/Polisi' => 'TNI/Polisi',
                                                                'Guru/Dosen' => 'Guru/Dosen',
                                                                'Pegawai Swasta' => 'Pegawai Swasta',
                                                                'Wiraswasta' => 'Wiraswasta',
                                                                'Pengacara/Jaksa/Hakim/Notaris' => 'Pengacara/Jaksa/Hakim/Notaris',
                                                                'Seniman/Pelukis/Artis/Sejenis' => 'Seniman/Pelukis/Artis/Sejenis',
                                                                'Dokter/Bidan/Perawat' => 'Dokter/Bidan/Perawat',
                                                                'Pilot/Pramugara' => 'Pilot/Pramugara',
                                                                'Pedagang' => 'Pedagang',
                                                                'Petani/Peternak' => 'Petani/Peternak',
                                                                'Nelayan' => 'Nelayan',
                                                                'Buruh (Tani/Pabrik/Bangunan)' => 'Buruh (Tani/Pabrik/Bangunan)',
                                                                'Sopir/Masinis/Kondektur' => 'Sopir/Masinis/Kondektur',
                                                                'Politikus' => 'Politikus',
                                                                'Lainnya' => 'Lainnya',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),

                                                        Select::make('w_pghsln_rt')
                                                            ->label('Penghasilan Rata-Rata')
                                                            ->placeholder('Pilih Penghasilan Rata-Rata')
                                                            ->options([
                                                                'Kurang dari 500.000' => 'Kurang dari 500.000',
                                                                '500.000 - 1.000.000' => '500.000 - 1.000.000',
                                                                '1.000.001 - 2.000.000' => '1.000.001 - 2.000.000',
                                                                '2.000.001 - 3.000.000' => '2.000.001 - 3.000.000',
                                                                '3.000.001 - 5.000.000' => '3.000.001 - 5.000.000',
                                                                'Lebih dari 5.000.000' => 'Lebih dari 5.000.000',
                                                                'Tidak ada' => 'Tidak ada',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),
                                                    ]),

                                                Grid::make(1)
                                                    ->schema([

                                                        Radio::make('w_tdk_hp')
                                                            ->label('Memiliki nomor handphone?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),

                                                        Radio::make('w_nomor_handphone_sama')
                                                            ->label('Apakah nomor handphone sama dengan Pendaftar?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_tdk_hp') !== 'Ya' ||
                                                                $get('ak_nomor_handphone_sama') !== 'Tidak' ||
                                                                $get('ik_nomor_handphone_sama') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya')
                                                            ->afterStateUpdated(function (Get $get, Set $set) {

                                                                if ($get('w_nomor_handphone_sama') === 'Ya') {
                                                                    $set('w_nomor_handphone', $get('hp_komunikasi'));
                                                                } else {
                                                                    $set('w_nomor_handphone', null);
                                                                }
                                                            })->columnSpanFull(),

                                                        TextInput::make('w_nomor_handphone')
                                                            ->label('No. Handphone')
                                                            ->helperText('Contoh: 82187782223')
                                                            // ->mask('82187782223')
                                                            ->prefix('62')
                                                            ->tel()
                                                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                                            ->required()
                                                            // ->disabled(fn (Get $get) =>
                                                            // $get('w_nomor_handphone_sama') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_tdk_hp') !== 'Ya' ||
                                                                $get('w_status') !== 'Lainnya'),
                                                    ]),

                                                // KARTU KELUARGA WALI
                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                                <p class="text-lg strong"><strong>C.02 KARTU KELUARGA</strong></p>
                                                <p class="text-lg strong"><strong>WALI</strong></p>
                                            </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Grid::make(2)
                                                    ->schema([

                                                        Radio::make('w_kk_sama_pendaftar')
                                                            ->label('Apakah KK dan Nama Kepala Keluarga sama dengan Pendaftar?')
                                                            ->live()
                                                            ->options([
                                                                'Ya' => 'Ya',
                                                                'Tidak' => 'Tidak',
                                                            ])
                                                            ->hidden(fn (Get $get) =>
                                                            $get('ak_kk_sama_pendaftar') !== 'Tidak' ||
                                                                $get('ik_kk_sama_pendaftar') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya')
                                                            ->afterStateUpdated(function (Get $get, Set $set) {

                                                                if ($get('w_kk_sama_pendaftar') === 'Ya') {
                                                                    $set('w_no_kk', $get('kartu_keluarga_santri'));
                                                                    $set('w_kep_kel_kk', $get('nama_kpl_kel_santri'));
                                                                } else {
                                                                    $set('w_no_kk', null);
                                                                    $set('w_kep_kel_kk', null);
                                                                }
                                                            })->columnSpanFull(),

                                                        TextInput::make('w_no_kk')
                                                            ->label('No. KK Wali')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->length(16)
                                                            ->required()
                                                            ->disabled(fn (Get $get) =>
                                                            $get('w_kk_sama_pendaftar') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),

                                                        TextInput::make('w_kep_kel_kk')
                                                            ->label('Nama Kepala Keluarga')
                                                            ->hint('Isi sesuai dengan KK')
                                                            ->hintColor('danger')
                                                            ->required()
                                                            ->disabled(fn (Get $get) =>
                                                            $get('w_kk_sama_pendaftar') === 'Ya')
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('w_status') !== 'Lainnya'),
                                                    ]),


                                                // ALAMAT WALI
                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                        <p class="text-lg strong"><strong>C.03 TEMPAT TINGGAL DOMISILI</strong></p>
                                        <p class="text-lg strong"><strong>WALI</strong></p>
                                    </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Radio::make('al_w_tgldi_ln')
                                                    ->label('Apakah tinggal di luar negeri?')
                                                    ->live()
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Textarea::make('al_w_almt_ln')
                                                    ->label('Alamat Luar Negeri')
                                                    ->required()
                                                    ->hidden(fn (Get $get) =>
                                                    $get('al_w_tgldi_ln') !== 'Ya'),

                                                Select::make('al_w_stts_rmh')
                                                    ->label('Status Kepemilikan Rumah')
                                                    ->placeholder('Pilih Status Kepemilikan Rumah')
                                                    ->options([
                                                        'Milik Sendiri' => 'Milik Sendiri',
                                                        'Rumah Orang Tua' => 'Rumah Orang Tua',
                                                        'Rumah Saudara/kerabat' => 'Rumah Saudara/kerabat',
                                                        'Rumah Dinas' => 'Rumah Dinas',
                                                        'Sewa/kontrak' => 'Sewa/kontrak',
                                                        'Lainnya' => 'Lainnya',
                                                    ])
                                                    ->searchable()
                                                    ->required()
                                                    ->native(false)
                                                    ->hidden(fn (Get $get) =>
                                                    $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                        $get('w_status') !== 'Lainnya'),

                                                Grid::make(2)
                                                    ->schema([

                                                        Select::make('al_w_provinsi_id')
                                                            ->label('Provinsi')
                                                            ->placeholder('Pilih Provinsi')
                                                            ->options(Provinsi::all()->pluck('provinsi', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya')
                                                            ->afterStateUpdated(function (Set $set) {
                                                                $set('al_w_kabupaten_id', null);
                                                                $set('al_w_kecamatan_id', null);
                                                                $set('al_w_kelurahan_id', null);
                                                                $set('al_w_kodepos', null);
                                                            }),

                                                        Select::make('al_w_kabupaten_id')
                                                            ->label('Kabupaten')
                                                            ->placeholder('Pilih Kabupaten')
                                                            ->options(fn (Get $get): Collection => Kabupaten::query()
                                                                ->where('provinsi_id', $get('al_w_provinsi_id'))
                                                                ->pluck('kabupaten', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya'),

                                                        Select::make('al_w_kecamatan_id')
                                                            ->label('Kecamatan')
                                                            ->placeholder('Pilih Kecamatan')
                                                            ->options(fn (Get $get): Collection => Kecamatan::query()
                                                                ->where('kabupaten_id', $get('al_w_kabupaten_id'))
                                                                ->pluck('kecamatan', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya'),

                                                        Select::make('al_w_kelurahan_id')
                                                            ->label('Kelurahan')
                                                            ->placeholder('Pilih Kelurahan')
                                                            ->options(fn (Get $get): Collection => Kelurahan::query()
                                                                ->where('kecamatan_id', $get('al_w_kecamatan_id'))
                                                                ->pluck('kelurahan', 'id'))
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->native(false)
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya')
                                                            ->afterStateUpdated(function (Get $get, ?string $state, Set $set, ?string $old) {

                                                                if (($get('al_w_kodepos') ?? '') !== Str::slug($old)) {
                                                                    return;
                                                                }

                                                                $kodepos = Kodepos::where('kelurahan_id', $state)->get('kodepos');

                                                                $state = $kodepos;

                                                                foreach ($state as $state) {
                                                                    $set('al_w_kodepos', Str::substr($state, 12, 5));
                                                                }
                                                            }),


                                                        TextInput::make('al_w_rt')
                                                            ->label('RT')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya'),

                                                        TextInput::make('al_w_rw')
                                                            ->label('RW')
                                                            ->required()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya'),

                                                        Textarea::make('al_w_alamat')
                                                            ->label('Alamat')
                                                            ->required()
                                                            ->columnSpanFull()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya'),

                                                        TextInput::make('al_w_kodepos')
                                                            ->label('Kodepos')
                                                            ->disabled()
                                                            ->required()
                                                            ->dehydrated()
                                                            ->hidden(fn (Get $get) =>
                                                            $get('al_w_tgldi_ln') !== 'Tidak' ||
                                                                $get('w_status') !== 'Lainnya'),
                                                    ]),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                             <p class="text-lg strong"><strong>Kajian yang diikuti</strong></p>
                                           </div>'))
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                Textarea::make('w_ustadz_kajian')
                                                    ->label('Ustadz yang mengisi kajian')
                                                    ->required()
                                                    // ->default('4232')
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),

                                                TextArea::make('w_tempat_kajian')
                                                    ->label('Tempat kajian yang diikuti')
                                                    ->required()
                                                    // ->default('4232')
                                                    ->hidden(fn (Get $get) =>
                                                    $get('w_status') !== 'Lainnya'),


                                            ])->compact()
                                        // ->collapsed(fn (Get $get): bool => $get('is_collapse')),

                                        // end of action steps
                                    ])
                            ]),
                        // end of Walisantri Tab

                        Tabs\Tab::make('Calon Santri')
                            ->schema([
                                Section::make('1. DAFTAR NAIK QISM')
                                    ->collapsed()
                                    ->compact()
                                    ->schema([

                                        Hidden::make('tahap')
                                            ->default('Tahap 1'),

                                        Hidden::make('jenispendaftar')
                                            ->default('NaikQism')
                                            ->live()
                                            ->afterStateUpdated(function ($record, Get $get, ?string $state, Set $set) {
                                                // dd($get('qism_id'));
                                                $set('qism_sebelumnya', 'a');
                                            }),

                                        Group::make()
                                            ->relationship('statussantri')
                                            ->schema([
                                                Hidden::make('ket_status')
                                                    ->default('NaikQism'),
                                            ]),

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div class="border-b">
                                                        <p class="text-lg strong"><strong>SANTRI</strong></p>
                                                    </div>')),

                                        TextInput::make('nama_lengkap')
                                            ->label('Nama Lengkap')
                                            ->disabled()
                                            ->required(),

                                        TextInput::make('nik')
                                            ->label('NIK Santri')
                                            ->length(16)
                                            ->disabled()
                                            ->required(),

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div class="border-b">
                                                        <p class="text-lg strong"><strong>QISM SAAT INI</strong></p>
                                                    </div>')),

                                        Group::make()
                                            ->relationship('kelassantri')
                                            ->schema([
                                                Hidden::make('mahad_id')
                                                    ->default(1),

                                                Select::make('qism_id')
                                                    ->label('Qism saat ini')
                                                    ->placeholder('Pilih Qism yang dituju')
                                                    ->options(Qism::all()->pluck('qism', 'id'))
                                                    ->disabled()
                                                    ->live()
                                                    ->required()
                                                    ->native(false),

                                                Select::make('qism_detail_id')
                                                    ->label('')
                                                    ->options(QismDetail::all()->pluck('qism_detail', 'id'))
                                                    ->disabled()
                                                    ->live()
                                                    ->required()
                                                    ->native(false),

                                                Select::make('kelas_id')
                                                    ->label('Kelas saat ini')
                                                    ->disabled()
                                                    ->native(false)
                                                    ->options(Kelas::all()->pluck('kelas', 'id'))
                                                    ->required(),
                                            ]),

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div class="border-b">
                                                        <p class="text-lg"><strong>DIDAFTARKAN UNTUK NAIK KE QISM</strong></p>
                                                    </div>')),

                                        TextInput::make('qism')
                                            ->label('Qism tujuan')
                                            ->disabled()
                                            ->required()
                                            ->live(),

                                        TextInput::make('qism_detail')
                                            ->label('')
                                            ->disabled()
                                            ->required()
                                            ->live(),
                                    ]),
                                // end of Section 1

                                Section::make('2. KUESIONER KESEHATAN')
                                    ->collapsed()
                                    ->compact()
                                    ->schema([

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div class="border-b">
                                                        <p class="text-lg strong"><strong>KUESIONER KESEHATAN</strong></p>
                                                    </div>')),
                                        Group::make()
                                            ->relationship('pendaftar')
                                            ->schema([
                                                Radio::make('ps_kkes_sakit_serius')
                                                    ->label('1. Apakah ananda pernah mengalami sakit yang cukup serius?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_sakit_serius_nama_penyakit')
                                                    ->label('Jika iya, kapan dan penyakit apa?')
                                                    ->required()
                                                    ->default('asdad')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_sakit_serius') !== 'Ya'
                                                    ),

                                                Radio::make('ps_kkes_terapi')
                                                    ->label('2. Apakah ananda pernah atau sedang menjalani terapi kesehatan?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_terapi_nama_terapi')
                                                    ->label('Jika iya, kapan dan terapi apa?')
                                                    ->required()
                                                    ->default('asdasd')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_terapi') !== 'Ya'
                                                    ),

                                                Radio::make('ps_kkes_kambuh')
                                                    ->label('3. Apakah ananda memiliki penyakit yang dapat/sering kambuh?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_kambuh_nama_penyakit')
                                                    ->label('Jika iya, penyakit apa?')
                                                    ->required()
                                                    ->default('asdad')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_kambuh') !== 'Ya'
                                                    ),

                                                Radio::make('ps_kkes_alergi')
                                                    ->label('4. Apakah ananda memiliki alergi terhadap perkara-perkara tertentu?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_alergi_nama_alergi')
                                                    ->label('Jika iya, sebutkan!')
                                                    ->required()
                                                    ->default('asdadsd')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_alergi') !== 'Ya'
                                                    ),

                                                Radio::make('ps_kkes_pantangan')
                                                    ->label('5. Apakah ananda mempunyai pantangan yang berkaitan dengan kesehatan?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_pantangan_nama')
                                                    ->label('Jika iya, sebutkan dan jelaskan alasannya!')
                                                    ->required()
                                                    ->default('asdadssad')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_pantangan') !== 'Ya'
                                                    ),

                                                Radio::make('ps_kkes_psikologis')
                                                    ->label('6. Apakah ananda pernah mengalami gangguan psikologis (depresi dan gejala-gejalanya)?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_psikologis_kapan')
                                                    ->label('Jika iya, kapan?')
                                                    ->required()
                                                    ->default('asdad')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_psikologis') !== 'Ya'
                                                    ),

                                                Radio::make('ps_kkes_gangguan')
                                                    ->label('7. Apakah ananda pernah mengalami gangguan jin?')
                                                    ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ])
                                                    ->live(),

                                                TextArea::make('ps_kkes_gangguan_kapan')
                                                    ->label('Jika iya, kapan?')
                                                    ->required()
                                                    ->default('asdadsad')
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kkes_gangguan') !== 'Ya'
                                                    ),

                                            ]),
                                    ]),
                                // end of Section 2

                                Section::make('3. KUESIONER KEMANDIRIAN')
                                    ->collapsed()
                                    ->compact()
                                    ->schema([

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div class="border-b">
                                                        <p class="text-lg"><strong>KUESIONER KEMANDIRIAN</strong></p>
                                                        <br>
                                                        <p class="text-sm"><strong>Kuesioner ini khusus untuk calon santri Pra Tahfidz kelas 1-4</strong></p>
                                                    </div>')),
                                        Group::make()
                                            ->relationship('pendaftar')
                                            ->schema([
                                                Radio::make('ps_kkm_bak')
                                                    ->label('1. Apakah ananda sudah bisa BAK sendiri?')
                                                    // ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ]),

                                                Radio::make('ps_kkm_bab')
                                                    ->label('2. Apakah ananda sudah bisa BAB sendiri?')
                                                    // ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ]),

                                                Radio::make('ps_kkm_cebok')
                                                    ->label('3. Apakah ananda sudah bisa cebok sendiri?')
                                                    // ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ]),

                                                Radio::make('ps_kkm_ngompol')
                                                    ->label('4. Apakah ananda masih mengompol?')
                                                    // ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ]),

                                                Radio::make('ps_kkm_disuapin')
                                                    ->label('5. Apakah makan ananda masih disuapi?')
                                                    // ->required()
                                                    ->default('Ya')
                                                    ->options([
                                                        'Ya' => 'Ya',
                                                        'Tidak' => 'Tidak',
                                                    ]),

                                            ]),
                                    ]),
                                // end of Section 3

                                Section::make('4. KUESIONER KEMAMPUAN PEMBAYARAN ADMINISTRASI')
                                    ->collapsed()
                                    ->compact()
                                    ->schema([

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div>
                                                        <p class="text-lg strong"><strong>KUESIONER KEMAMPUAN PEMBAYARAN ADMINISTRASI</strong></p>
                                                    </div>')),

                                        Placeholder::make('')
                                            ->content(new HtmlString('<div class="border-b">
                                                        <p class="text-lg strong"><strong>RINCIAN BIAYA AWAL DAN SPP</strong></p>
                                                    </div>')),
                                        Group::make()
                                            ->relationship('pendaftar')
                                            ->schema([
                                                Placeholder::make('')
                                                    ->content(new HtmlString(
                                                        '<div class="grid grid-cols-1 justify-center">

                                                <div class="border rounded-xl p-4">
                                                <table>
                                                    <!-- head -->
                                                    <thead>
                                                        <tr class="border-b">
                                                            <th class="text-lg text-tsn-header" colspan="4">QISM PRA TAHFIDZ-FULLDAY (tanpa makan)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                <!-- row 1 -->
                                                <tr>
                                                    <th class="text-start">Uang Pendaftaran     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">100.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 2 -->
                                                <tr>
                                                    <th class="text-start">Uang Gedung      </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">300.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 3 -->
                                                <tr>
                                                    <th class="text-start">Uang Sarpras     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">200.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 4 -->
                                                <tr class="border-tsn-header">
                                                    <th class="text-start">SPP*     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">200.000</td>
                                                    <td class="text-end">(per bulan)</td>
                                                </tr>
                                                <tr class="border-t">
                                                    <th>Total       </th>
                                                    <td class="text-end"><strong>Rp.</strong></td>
                                                    <td class="text-end"><strong>800.000</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm" colspan="4">*Pembayaran administrasi awal termasuk SPP bulan pertama</td>
                                                </tr>
                                                </tbody>
                                                    </table>
                                                </div>

                                                <br>

                                                <div class="border rounded-xl p-4">
                                                <table>
                                                    <!-- head -->
                                                    <thead>
                                                        <tr class="border-b">
                                                            <th class="text-lg text-tsn-header" colspan="4">QISM PRA TAHFIDZ-FULLDAY (dengan makan)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                <!-- row 1 -->
                                                <tr>
                                                    <th class="text-start">Uang Pendaftaran     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">100.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 2 -->
                                                <tr>
                                                    <th class="text-start">Uang Gedung      </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">300.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 3 -->
                                                <tr>
                                                    <th class="text-start">Uang Sarpras     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">200.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 4 -->
                                                <tr class="border-tsn-header">
                                                    <th class="text-start">SPP*     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">300.000</td>
                                                    <td class="text-end">(per bulan)</td>
                                                </tr>
                                                <tr class="border-t">
                                                    <th>Total       </th>
                                                    <td class="text-end"><strong>Rp.</strong></td>
                                                    <td class="text-end"><strong>900.000</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm" colspan="4">*Pembayaran administrasi awal termasuk SPP bulan pertama</td>
                                                </tr>
                                                </tbody>
                                                    </table>
                                                </div>
                                                </div>

                                                <br>

                                                <div class="border rounded-xl p-4">
                                                <table>
                                                    <!-- head -->
                                                    <thead>
                                                        <tr class="border-b">
                                                            <th class="text-lg text-tsn-header" colspan="4">QISM PT (menginap), TQ, IDD, MTW, TN</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                <!-- row 1 -->
                                                <tr>
                                                    <th class="text-start">Uang Pendaftaran     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">100.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 2 -->
                                                <tr>
                                                    <th class="text-start">Uang Gedung      </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">300.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 3 -->
                                                <tr>
                                                    <th class="text-start">Uang Sarpras     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">200.000</td>
                                                    <td class="text-end">(per tahun)</td>
                                                </tr>
                                                <!-- row 4 -->
                                                <tr class="border-tsn-header">
                                                    <th class="text-start">SPP*     </th>
                                                    <td class="text-end">Rp.</td>
                                                    <td class="text-end">550.000</td>
                                                    <td class="text-end">(per bulan)</td>
                                                </tr>
                                                <tr class="border-t">
                                                    <th>Total       </th>
                                                    <td class="text-end"><strong>Rp.</strong></td>
                                                    <td class="text-end"><strong>1.150.000</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm" colspan="4">*Pembayaran administrasi awal termasuk SPP bulan pertama</td>
                                                </tr>
                                                </tbody>
                                                    </table>
                                                </div>'
                                                    )),

                                                Radio::make('ps_kadm_status')
                                                    ->label('Status anak didik terkait dengan administrasi')
                                                    ->required()
                                                    ->default('Santri/Santriwati tidak mampu')
                                                    ->options([
                                                        'Santri/Santriwati mampu (tidak ada permasalahan biaya)' => 'Santri/Santriwati mampu (tidak ada permasalahan biaya)',
                                                        'Santri/Santriwati tidak mampu' => 'Santri/Santriwati tidak mampu',
                                                    ])
                                                    ->live(),

                                                Placeholder::make('')
                                                    ->content(new HtmlString('<div class="border-b">
                                                                            <p><strong>Bersedia memenuhi persyaratan sebagai berikut:</strong></p>
                                                                        </div>'))
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),

                                                Radio::make('ps_kadm_surat_subsidi')
                                                    ->label('1. Wali harus membuat surat permohonan subsidi/ keringanan biaya administrasi')
                                                    ->required()
                                                    ->default('Bersedia')
                                                    ->options([
                                                        'Bersedia' => 'Bersedia',
                                                        'Tidak bersedia' => 'Tidak bersedia',
                                                    ])
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),

                                                Radio::make('ps_kadm_surat_kurang_mampu')
                                                    ->label('2. Wali harus menyertakan surat keterangan kurang mampu dari ustadz salafy setempat SERTA dari aparat pemerintah setempat, yang isinya menyatakan bhw mmg kluarga tersebut "perlu dibantu"')
                                                    ->required()
                                                    ->default('Bersedia')
                                                    ->options([
                                                        'Bersedia' => 'Bersedia',
                                                        'Tidak bersedia' => 'Tidak bersedia',
                                                    ])
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),

                                                Radio::make('ps_kadm_atur_keuangan')
                                                    ->label('3. Keuangan ananda akan dipegang dan diatur oleh Mahad')
                                                    ->required()
                                                    ->default('Bersedia')
                                                    ->options([
                                                        'Bersedia' => 'Bersedia',
                                                        'Tidak bersedia' => 'Tidak bersedia',
                                                    ])
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),

                                                Radio::make('ps_kadm_penentuan_subsidi')
                                                    ->label('4. Yang menentukan bentuk keringanan yang diberikan adalah Mahad')
                                                    ->required()
                                                    ->default('Bersedia')
                                                    ->options([
                                                        'Bersedia' => 'Bersedia',
                                                        'Tidak bersedia' => 'Tidak bersedia',
                                                    ])
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),

                                                Radio::make('ps_kadm_hidup_sederhana')
                                                    ->label('5. Ananda harus berpola hidup sederhana agar tidak menimbulkan pertanyaan pihak luar')
                                                    ->required()
                                                    ->default('Bersedia')
                                                    ->options([
                                                        'Bersedia' => 'Bersedia',
                                                        'Tidak bersedia' => 'Tidak bersedia',
                                                    ])
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),

                                                Radio::make('ps_kadm_kebijakan_subsidi')
                                                    ->label('6. Kebijakan subsidi bisa berubah sewaktu waktu')
                                                    ->required()
                                                    ->default('Bersedia')
                                                    ->options([
                                                        'Bersedia' => 'Bersedia',
                                                        'Tidak bersedia' => 'Tidak bersedia',
                                                    ])
                                                    ->hidden(
                                                        fn (Get $get) =>
                                                        $get('ps_kadm_status') !== 'Santri/Santriwati tidak mampu'
                                                    ),
                                            ]),
                                    ]),
                                // end of Section 4
                            ]),



                    ])->columnSpanFull()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->defaultPaginationPageOption('20')
            ->columns([

                // TextColumn::make('index')
                //     ->rowIndex(),

                TextColumn::make('nama_lengkap')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status_tahap')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lolos' => 'success',
                        'Tidak Lolos' => 'danger',
                        'Diterima' => 'success',
                        'Tidak Diterima' => 'danger',
                    }),

                TextColumn::make('tahap')
                    ->label('Tahap')
                    ->default('Tahap 1')
                    ->sortable(),

                TextColumn::make('pendaftar.ps_kadm_status')
                    ->label('Adm')
                    ->formatStateUsing(function (Model $record) {
                        $pendaftar = Pendaftar::where('santri_id', $record->id)->first();
                        // dd($pendaftar->ps_kadm_status);
                        if ($pendaftar->ps_kadm_status === 'Santri/Santriwati mampu (tidak ada permasalahan biaya)') {
                            return ('Mampu');
                        } elseif ($pendaftar->ps_kadm_status === 'Santri/Santriwati tidak mampu') {
                            return ('Tidak Mampu');
                        }
                    }),

                TextColumn::make('file_kk')
                    ->label('Kartu Keluarga')
                    ->description(fn (): string => 'Kartu Keluarga', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Kartu Keluarga<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_kk !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_kk);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_akte')
                    ->label('Akte')
                    ->description(fn (): string => 'Akte', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Akte<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_akte !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_akte);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_srs')
                    ->label('Surat Rekomendasi')
                    ->description(fn (): string => 'Surat Rekomendasi', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Rekomendasi<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_srs !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_srs);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_ijz')
                    ->label('Ijazah')
                    ->description(fn (): string => 'Ijazah', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Ijazah<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_ijz !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_ijz);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_cvd')
                    ->label('Sertifikat Vaksin Cvd-19')
                    ->description(fn (): string => 'Sertifikat Vaksin Cvd-19', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Sertifikat Vaksin Cvd-19<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_cvd !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_cvd);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_skt')
                    ->label('Surat Keterangan Taklim')
                    ->description(fn (): string => 'Surat Keterangan Taklim', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Keterangan Taklim<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_skt !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_skt);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_skuasa')
                    ->label('Surat Kuasa')
                    ->description(fn (): string => 'Surat Kuasa', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Kuasa<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_skuasa !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_skuasa);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_spkm')
                    ->label('Surat Pernyataan Kesanggupan')
                    ->description(fn (): string => 'Surat Pernyataan Kesanggupan', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Pernyataan Kesanggupan<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_spkm !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_spkm);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_pka')
                    ->label('Surat Permohonan Keringanan Administrasi')
                    ->description(fn (): string => 'Surat Permohonan Keringanan Administrasi', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Permohonan Keringanan Administrasi<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_pka !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_pka);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_ktmu')
                    ->label('Surat Keterangan Tidak Mampu (U)')
                    ->description(fn (): string => 'Surat Keterangan Tidak Mampu (U)', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Keterangan Tidak Mampu (U)<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_ktmu !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_ktmu);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),

                TextColumn::make('file_ktmp')
                    ->label('Surat Keterangan Tidak Mampu (P)')
                    ->description(fn (): string => 'Surat Keterangan Tidak Mampu (P)', position: 'above')
                    // ->color('white')
                    ->formatStateUsing(fn (string $state): string => __("Lihat"))
                    // ->limit(1)
                    ->icon('heroicon-s-eye')
                    ->iconColor('success')
                    // ->circular()
                    ->alignCenter()
                    ->placeholder(new HtmlString('Surat Keterangan Tidak Mampu (P)<br>Belum Upload'))
                    ->url(function (Model $record) {
                        if ($record->file_ktmp !== null) {

                            return ("https://psb.tsn.ponpes.id/storage/" . $record->file_ktmp);
                        }
                    })
                    ->badge()
                    ->color('gray')
                    ->openUrlInNewTab(),



                TextColumn::make('qism_detail')
                    ->label('Qism')
                    ->hidden()
                    ->sortable(),

                // TextColumn::make('walisantri.hp_komunikasi')
                //     ->label('Nomor HP')
                //     ->url(fn ($state) => 'https://wa.me/62' . $state)
                //     ->openUrlInNewTab()
                //     ->tooltip('Klik untuk menghubungi melalui WA')
                //     ->icon('phosphor-whatsapp-logo-light')
                //     ->iconColor('success'),

            ])
            ->groups([
                GroupingGroup::make('qism_detail')
                    ->titlePrefixedWithLabel(false)
            ])
            ->defaultGroup('qism_detail')
            ->defaultSort('nama_lengkap')
            ->filters([

                SelectFilter::make('qism_detail_id')
                    ->multiple()
                    ->options([
                        '3' => 'PTPa',
                        '4' => 'PTPi',
                        '5' => 'TQPa',
                        '6' => 'TQPi',
                        '7' => 'IDD',
                        '8' => 'MTW',
                    ])
                    ->hidden(!auth()->user()->id === 1 || !auth()->user()->id === 2),

                SelectFilter::make('tahap')
                    ->multiple()
                    ->options([
                        'Tahap 1' => 'Tahap 1',
                        'Tahap 2' => 'Tahap 2',
                        'Tahap 3' => 'Tahap 3',
                    ]),

                SelectFilter::make('status_tahap')
                    ->label('Status Tahap')
                    ->multiple()
                    ->options([
                        'Lolos' => 'Lolos',
                        'Tidak Lolos' => 'Tidak Lolos',
                        'Diterima' => 'Diterima',
                        'Tidak Diterima' => 'Tidak Diterima',
                    ]),

            ], layout: FiltersLayout::AboveContent)
            ->actions([
                ViewAction::make('Lihat')
                    ->label('Lihat')
                    ->button()
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkAction::make('lolostahap1')
                    ->label(__('Lolos Tahap 1'))
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalIconColor('success')
                    ->modalHeading('Ubah Status Santri Lolos ke Tahap Selanjutnya?')
                    ->modalDescription('Setelah klik tombol "Simpan", maka status akan berubah')
                    ->modalSubmitActionLabel('Simpan')
                    ->action(fn (Collection $records, array $data) => $records->each(
                        function ($record) {

                            $data['status_tahap'] = 'Lolos';
                            $data['tahap'] = 'Tahap 2';
                            $data['deskripsitahap'] = 'Alhamdulillah, ananda lolos ke tahap berikutnya';
                            $data['jenispendaftar'] = 'naikqism';
                            $record->update($data);

                            return $record;

                            Notification::make()
                                ->success()
                                ->title('Status Ananda telah diupdate')
                                ->persistent()
                                ->color('Success')
                                ->send();
                        }
                    ))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('tidaklolostahap1')
                    ->label(__('Tidak Lolos Tahap 1'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->modalHeading(new HtmlString('Ubah Status Santri <strong class="text-red">Tidak</strong> Lolos ke Tahap Selanjutnya?'))
                    ->modalDescription('Setelah klik tombol "Simpan", maka status akan berubah')
                    ->modalSubmitActionLabel('Simpan')
                    ->action(fn (Collection $records, array $data) => $records->each(
                        function ($record) {

                            $data['status_tahap'] = 'Tidak Lolos';
                            $data['tahap'] = 'Tahap 1';
                            $data['deskripsitahap'] = 'Ananda tidak dapat melanjutkan ke tahap berikutnya';
                            $data['jenispendaftar'] = 'naikqism';
                            $record->update($data);

                            return $record;

                            Notification::make()
                                ->success()
                                ->title('Status Ananda telah diupdate')
                                ->persistent()
                                ->color('Success')
                                ->send();
                        }
                    ))->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('diterima')
                    ->label(__('Diterima'))
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalIconColor('success')
                    ->modalHeading('Ubah Status menjadi "Diterima sebagai santri?"')
                    ->modalDescription('Setelah klik tombol "Simpan", maka status akan berubah')
                    ->modalSubmitActionLabel('Simpan')
                    ->action(fn (Collection $records, array $data) => $records->each(
                        function ($record) {

                            $cektahap = $record->tahap;

                            // dd($cektahap);

                            if ($cektahap === 'Tahap 2') {

                                // logic switch case dibagi menjadi 2 antara MTW/TN dan selain MTW/TN, karena beda di tahun ajarannya
                                // mendapatkan id qism tujuan
                                $cekqismtujuan = $record->qism_id;

                                switch (true) {
                                        // case qism tujuan = MTW atau TN
                                    case ($cekqismtujuan === '5' || $cekqismtujuan === '6'):

                                        //cek apakah data naik qism di table kelas_santris sudah terbuat dengan tahun ajaran yang baru
                                        $cektahunajaran = KelasSantri::where('santri_id', $record->id)
                                            ->where('tahun_ajaran_id', '6')
                                            ->count();

                                        // dd($cektahunajaran);

                                        // jika data naik qism belum terbuat di table kelas_santris, maka buat data baru
                                        if ($cektahunajaran === 0) {

                                            //mendapatkan id qism tujuan, untuk menentukan kelas_id
                                            $cekqism = $record->qism_id;

                                            //jika qism adalah MTW, maka kelas_id = 9
                                            if ($cekqism === '5') {

                                                $kelassantri = new KelasSantri;

                                                $kelassantri->santri_id = $record->id;
                                                $kelassantri->mahad_id = '1';
                                                $kelassantri->tahun_ajaran_id = '6';
                                                $kelassantri->semester_id = '3';
                                                $kelassantri->qism_id = $record->qism_id;
                                                $kelassantri->qism_detail_id = $record->qism_detail_id;
                                                $kelassantri->kelas_id = '9';

                                                $kelassantri->save();

                                                $data['status_tahap'] = 'Diterima';
                                                $data['tahap'] = 'Tahap 3';
                                                $data['deskripsitahap'] = 'Alhamdulillah, ananda diterima sebagai santri Mahad Tadzimussunnah Sine Ngawi';
                                                $record->update($data);

                                                return $record;

                                                Notification::make()
                                                    ->success()
                                                    ->title('Status Ananda telah diupdate')
                                                    ->persistent()
                                                    ->color('Success')
                                                    ->send();

                                                //jika qism adalah TN, maka kelas_id = 7
                                            } elseif ($cekqism === '6') {

                                                $kelassantri = new KelasSantri;

                                                $kelassantri->santri_id = $record->id;
                                                $kelassantri->mahad_id = '1';
                                                $kelassantri->tahun_ajaran_id = '6';
                                                $kelassantri->semester_id = '3';
                                                $kelassantri->qism_id = $record->qism_id;
                                                $kelassantri->qism_detail_id = $record->qism_detail_id;
                                                $kelassantri->kelas_id = '7';

                                                $kelassantri->save();

                                                $data['status_tahap'] = 'Diterima';
                                                $data['tahap'] = 'Tahap 3';
                                                $data['deskripsitahap'] = 'Alhamdulillah, ananda diterima sebagai santri Mahad Tadzimussunnah Sine Ngawi';
                                                $record->update($data);

                                                return $record;

                                                Notification::make()
                                                    ->success()
                                                    ->title('Status Ananda telah diupdate')
                                                    ->persistent()
                                                    ->color('Success')
                                                    ->send();
                                            }

                                            // jika data naik qism TELAH dibuat di table kelas_santris, maka return
                                        } elseif ($cektahunajaran === 1) {
                                            dd('telah update');
                                            return;
                                        }

                                        break;

                                        // case qism tujuan = selain MTW atau TN
                                    case ($cekqismtujuan !== '5' || $cekqismtujuan !== '6'):
                                        // dd('qism tujuan !== MTW atau TN');

                                        $kelassantri = new KelasSantri;

                                        $kelassantri->santri_id = $record->id;
                                        $kelassantri->mahad_id = '1';
                                        $kelassantri->tahun_ajaran_id = '7';
                                        $kelassantri->semester_id = '1';
                                        $kelassantri->qism_id = $record->qism_id;
                                        $kelassantri->qism_detail_id = $record->qism_detail_id;
                                        $kelassantri->kelas_id = '1';

                                        $kelassantri->save();

                                        $data['status_tahap'] = 'Diterima';
                                        $data['tahap'] = 'Tahap 3';
                                        $data['deskripsitahap'] = 'Alhamdulillah, ananda diterima sebagai santri Mahad Tadzimussunnah Sine Ngawi';
                                        $record->update($data);

                                        return $record;

                                        Notification::make()
                                            ->success()
                                            ->title('Status Ananda telah diupdate')
                                            ->persistent()
                                            ->color('Success')
                                            ->send();
                                        break;
                                }
                            } elseif ($cektahap !== 'Tahap 2') {
                                Notification::make()
                                    // ->success()
                                    ->title('Status santri masih Tahap 1!')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->iconColor('danger')
                                    ->persistent()
                                    ->color('warning')
                                    ->send();
                            }
                        }
                    ))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('tidakditerima')
                    ->label(__('Tidak Diterima'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->modalHeading('Ubah Status menjadi "Tidak diterima sebagai santri?"')
                    ->modalDescription('Setelah klik tombol "Simpan", maka status akan berubah')
                    ->modalSubmitActionLabel('Simpan')
                    ->action(fn (Collection $records, array $data) => $records->each(
                        function ($record) {

                            $cektahap = $record->tahap;

                            // dd($cektahap);

                            if ($cektahap === 'Tahap 2') {

                                $statussantri = StatusSantri::where('santri_id', $record->id)->first();
                                $statussantri->status = 'Tidak Aktif';
                                $statussantri->save();

                                $data['status_tahap'] = 'Tidak Diterima';
                                $data['deskripsitahap'] = 'Qodarullah, ananda tidak diterima sebagai santri Mahad Tadzimussunnah Sine Ngawi';
                                $record->update($data);

                                return $record;

                                Notification::make()
                                    ->success()
                                    ->title('Status Ananda telah diupdate')
                                    ->persistent()
                                    ->color('Success')
                                    ->send();
                            } elseif ($cektahap !== 'Tahap 2') {
                                Notification::make()
                                    // ->success()
                                    ->title('Status santri masih Tahap 1!')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->iconColor('danger')
                                    ->persistent()
                                    ->color('warning')
                                    ->send();
                            }
                        }
                    ))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('reset')
                    ->label(__('Reset'))
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-arrow-path')
                    ->modalIconColor('gray')
                    ->modalHeading(new HtmlString('Reset Status Santri ke Tahap sebelumnya?'))
                    ->modalDescription('Setelah klik tombol "Simpan", maka status akan berubah')
                    ->modalSubmitActionLabel('Simpan')
                    ->action(fn (Collection $records, array $data) => $records->each(
                        function ($record) {

                            $data['status_tahap'] = null;
                            $data['tahap'] = 'Tahap 1';
                            $data['deskripsitahap'] = null;
                            $data['jenispendaftar'] = 'naikqism';
                            $record->update($data);

                            return $record;

                            Notification::make()
                                ->success()
                                ->title('Status Ananda telah diupdate')
                                ->persistent()
                                ->color('Success')
                                ->send();
                        }
                    ))->deselectRecordsAfterCompletion()

            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->status_tahap === 'Lolos' || $record->status_tahap === null,
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ListPendaftarNaikQism::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ListPendaftarNaikQism::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendaftarNaikQisms::route('/'),
            // 'create' => Pages\CreatePendaftarNaikQism::route('/create'),
            'view' => Pages\ViewPendaftarNaikQism::route('/{record}'),
            // 'edit' => Pages\EditPendaftarNaikQism::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {

        // return parent::getEloquentQuery()->where('qism_id', Auth::user()->mudirqism)->orWhere('');

        return parent::getEloquentQuery()
            ->whereIn('qism_id', Auth::user()->mudirqism)
            ->where('daftarnaikqism', 'Mendaftar');
    }
}
