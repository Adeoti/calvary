<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Studenti;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SchoolClass;
use Filament\Resources\Resource;
use function Illuminate\Log\log;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Container\Attributes\Log;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;

use App\Filament\Resources\StudentiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StudentiResource\RelationManagers;

class StudentiResource extends Resource
{
    protected static ?string $model = User::class;


    protected static ?string $modelLabel = "Student";
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'HRM';

    public $class_id;
 

    public static function getNavigationBadge(): ?string
    {
        return User::whereHas('student')->count();
    }
    public static function getNavigationBadgeColor(): ?string
{
    return 'success';
}
    public static function form(Form $form): Form
    {
        return $form
       
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->required()
                            ->email()
                            ->visibleOn('create')
                            ->maxLength(255)
                            ->unique(User::class, 'email'),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(Branch::all()->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('school_class_id', null); // Reset class_id on branch change
                            }),

                            TextInput::make('password')
                            ->label('Password')
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable()
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->minLength(8),
                        

                        TextInput::make('address')
                            ->label('Address')
                            ->nullable(),

                        // Student's class for creating
                        Select::make('student_class')
                            ->default('Hello')
                            ->label('Class')
                            ->relationship('student', 'school_class_id')
                            ->options(function (callable $get) {
                                $branchId = $get('branch_id');
                                log("Is = ".$branchId);
                                return $branchId
                                    ? SchoolClass::whereJsonContains('branch_ids', $branchId)->pluck('name', 'id')
                                    : ['0' => 'No matched classes found'];
                            })
                            ->visibleOn('create')
                            ->reactive()
                            
                            ->required(),

                            //Student's class for updating
                        Select::make('student_class')
                            ->default('Hello')
                            ->label('Class')
                            ->relationship('student', 'school_class_id')
                            ->options(function (Model $record) {
                               $branch_id = (string)$record->branch_id;
                                  $classes_arr =  SchoolClass::whereJsonContains('branch_ids', $branch_id)->pluck('name', 'id');
                                    

                                    return $classes_arr;
                            })
                            ->visibleOn('edit')
                            ->reactive()
                            
                            ->required(),

                        FileUpload::make('passport')
                            ->label('Passport Photo')
                            ->nullable()
                            ->disk('public')
                            ->columnSpanFull()
                            ->uploadingMessage('Uploading Passport')
                            ->directory('passports'),
                    ])->columns(2),

                Forms\Components\Section::make('Student Information')
                    ->relationship('student')
                    ->schema([
                       
                        TextInput::make('roll_number')
                            ->label('Registration Number')
                            // ->visibleOn('create')
                            ->unique(Student::class, 'roll_number', ignoreRecord: true)
                            ->required(),
                        DatePicker::make('admission_date')
                            ->label('Admission Date')
                            ->required(),
                            // Section::make('Parent Details')
                            // ->schema([
                                TextInput::make('parent_contact')
                                    ->label('Parent Contact')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('guardian_name')
                                    ->label('Guardian Name')
                                    ->required(),
                                TextInput::make('guardian_phone')
                                    ->label('Guardian Phone')
                                    ->required(),
                            // ])->columns(3),
                    ])->columns(2),


            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->with(['student', 'branch', 'class'])
                    ->orderBy('id', 'desc')
                    ->whereHas('student')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Student_ID')
                    ->copyable()
                    ->copyMessage('Student ID Copied')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('passport')
                    ->label('Passport Photo')
                    //insert a placeholder image or icon for a student that doesn't have a passport
                    ->circular(),

                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.roll_number')
                    ->label('Registration Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                // Add filters here if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentis::route('/'),
            'create' => Pages\CreateStudenti::route('/create'),
            'edit' => Pages\EditStudenti::route('/{record}/edit'),
        ];
    }
}
