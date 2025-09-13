<x-filament-panels::page>
    @if($resultUploads->isEmpty())
        <p>No results uploaded for this result root.</p>
    @else
        {{-- Group results by class --}}

       <div style="background: #003333; color:#fff; text-align:center; padding:10px 0px;">
        <a href="{{ route('download-report-cards', $record->id) }}" class="btn btn-primary" style="border-radius:10px; border:1px solid #fff; padding:5px 10px;" >Download Report Cards as PDF</a>
      
        
    </div> 
       


        @php
            $resultsByClass = [];
            $classNames = [];

            foreach ($resultUploads as $resultUpload) {
                $class = App\Models\SchoolClass::find($resultUpload->class_id);

                
                $className = $class->name ?? 'Unknown Class';

                // Group results by class ID
                $resultsByClass[$resultUpload->class_id][] = $resultUpload;
                $classNames[$resultUpload->class_id] = $className;
            }
        @endphp

        {{-- Tabs for each class --}}
        <div class="tabs">
            <ul class="flex mb-4 border-b">
                @foreach($classNames as $classId => $className)
                    <li class="mr-2">
                        <button 
                            class="tab-toggle px-4 py-2 rounded-t-lg bg-gray-200 hover:bg-gray-300" 
                            data-tab="tab-{{ $classId }}">{{ $className }}</button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Tab Content for each class --}}
        @foreach($resultsByClass as $classId => $classResults)
            <div class="tab-content hidden" id="tab-{{ $classId }}">
                {{-- Class Header --}}
                <h2 class="text-2xl font-semibold mb-4">{{ $classNames[$classId] }}</h2>

                {{-- Collect students and dynamic headers --}}
                @php
                    $students = [];
                    $dynamicHeaders = []; // To track all possible score headers dynamically


                


                 // Prepare an array of total scores for all students
    $studentsWithScores = [];

    foreach ($students as $studentId => $studentData) {
        $totalScore = array_sum(array_column($studentData['subjects'], 'total'));
        $studentsWithScores[$studentId] = $totalScore;
    }

    // Sort the students by total score in descending order to rank them
    arsort($studentsWithScores);

    // Assign ranks with ordinal suffixes
    $positions = [];
    $rank = 1;
    foreach ($studentsWithScores as $studentId => $score) {
        $positions[$studentId] = ordinal_suffix($rank++);
    }

    // Helper function for ordinal suffix
    function ordinal_suffix($number)
    {
        $suffixes = ['th', 'st', 'nd', 'rd'];
        $value = $number % 100;

        return $number . ($suffixes[($value - 20) % 10] ?? $suffixes[$value] ?? $suffixes[0]);
    }


                    foreach ($classResults as $resultUpload) {
                        $subject = App\Models\Subject::find($resultUpload->subject_id);
                        $cardItems = is_array($resultUpload->card_items) ? $resultUpload->card_items : json_decode($resultUpload->card_items, true);

                        foreach ($cardItems as $studentId => $result) {
                            $student = App\Models\User::find($studentId);
                            if ($student) {
                                $students[$studentId]['info'] = $student;
                                $students[$studentId]['subjects'][] = [
                                    'name' => $subject->name ?? 'No Subject',
                                    'scores' => $result['scores'] ?? [],
                                    'total' => $result['total'] ?? 'N/A',
                                    'average' => $result['average'] ?? 'N/A',
                                    'highest' => $result['highest'] ?? 'N/A',
                                    'lowest' => $result['lowest'] ?? 'N/A',
                                    // 'position' => $result['position'] ?? 'N/A',
                                    'grade' => $result['grade'] ?? 'N/A',
                                    'remark' => $result['remark'] ?? 'N/A',
                                ];

                            //   Calculate total score of each student by subject
                         
                                

                                // echo json_encode($result['position']);
                                // Collect headers dynamically
                                $dynamicHeaders = array_unique(array_merge($dynamicHeaders, array_keys($result['scores'] ?? [])));
                            }
                        }
                    }
                    $school_logo = $schoolDetails['school_logo'];
                    $principal_signature = $schoolDetails['principal_signature'];
                @endphp

                {{-- Render cards for each student --}}
                @foreach ($students as $studentId => $studentData)
                <div style="height:10px; background:#eaf0f8;"></div>
                    <div class="border p-6 mb-6 rounded-lg shadow-lg" style="margin-top:15px; margin-bottom:15px;">
                        <div class="mb-4 flex justify-between border p-2">
                            <div class="school_logo">
                                <img src="{{ $record->logo ? Storage::url($record->logo) : Storage::url($school_logo) }}" alt="Logo" class="logo-img" style="height: 70px; border-radius: 10%;">
                            </div>
                          <div class="text-center">  <h2 class="font-bold" style="font-size: 2.7rem;">{{ $schoolDetails['school_name'] }}</h2>
                            <p><b>Address: </b> {{ $record->section_address ?? $schoolDetails['school_address'] }}</p>
                            <p><b>Phone:</b> {{ $schoolDetails['school_phone'] }}</p>
                            </div>
                            <div class="student_passport">
                                <img src="{{ Storage::url($studentData['info']->passport) }}" alt="Logo" class="logo-img" style="height: 70px; border-radius: 10%;">
                            </div>
                        </div>
                        
                        
                        {{-- Student Info --}}
                        

                <div class="mb-4 flex justify-between border p-2">
                   
                    <div>
                     <h2 class="text-xl font-bold">{{ $studentData['info']->name }}</h2>
                     <p>Student ID: {{ $studentData['info']->id }}</p>
                     <p>Email: {{ $studentData['info']->email }}</p>
                     {{-- Count attendance where status = Present and result_root_id = $record->id and student_id = $studentData['info']->id --}}
                     <p>Attendance: {{ App\Models\Attendance::where('result_root_id', $record->id)->where('student_id', $studentData['info']->id)->count() }}</p>
                    </div>
 
                    @php
                     //    $student = App\Models\User::find($studentData['info']->id);
                        // $number_in_class = App\Models\User::whereHas('student')->where('student_class', $student->student_class)->count();

                        if ($student && $student->student_class) {
                            $number_in_class = App\Models\User::whereHas('student', function ($query) use ($student) {
                                $query->where('student_class', $student->student_class);
                            })->count();
                        } else {
                            $number_in_class = ""; // Default value if $student or $student->student_class is null
                        }

                    @endphp
 
                       <!-- Student Details Column -->
             <div class="details-column">
                <p class="detail-item"><span class="bold" style="font-weight: 600; color:darkmagenta;">{{$record->name}}</span></p>
                 <p class="detail-item"><span class="bold">Roll Number:</span> {{ $student->student->roll_number ?? 'N/A' }}</p>
                 <p class="detail-item"><span class="bold">Guardian:</span> {{ $student->student->guardian_name ?? 'N/A' }}</p>
                 <p>Times present: {{ App\Models\Attendance::where('status', 'Present')->where('result_root_id', $record->id)->where('student_id', $studentData['info']->id)->count() }}</p>
                 
             </div>
 
             <!-- Student Contact Column -->
             <div class="contact-column">
                 
                 
                 <p class="contact-item"><span class="bold">Class:</span> {{ $class->name ?? 'N/A' }}</p>
                 <p class="contact-item"><span class="bold">Number In Class:</span> {{ $number_in_class ?? 'N/A' }}</p>
                 <p class="contact-item">
                    <span class="bold">Next Term Begins:</span> 
                    {{ $record->next_term ? \Carbon\Carbon::parse($record->next_term)->format('M j, Y') : 'N/A' }}
                </p>
                
                 
             </div>
                     
                 
                 
                 </div>

                        {{-- Subjects Table --}}
                        <table class="w-full border-collapse border border-gray-300 text-left">
                            <thead class="bg-gray-200">
                                <tr class="table-head">
                                    <th class="border px-2 py-1">SUBJECT</th>
                                    @foreach ($dynamicHeaders as $header)
                                        <th class="border px-2 py-1">{{ $header }}</th>
                                    @endforeach
                                    <th class="border px-2 py-1">TOTAL</th>
                                    <th class="border px-2 py-1">AVERAGE</th>
                                    <th class="border px-2 py-1">HIGHEST</th>
                                    <th class="border px-2 py-1">LOWEST</th> <!-- Add this -->
                                    {{-- <th class="border px-2 py-1">POSITION</th> <!-- Add this --> --}}
                                    <th class="border px-2 py-1">GRADE</th>
                                    <th class="border px-2 py-1">REMARK</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentData['subjects'] as $subject)
                                    <tr>
                                        <td class="border px-2 py-1">{{ $subject['name'] }}</td>
                                        @foreach ($dynamicHeaders as $header)
                                            <td class="border px-2 py-1">{{ $subject['scores'][$header] ?? 'N/A' }}</td>
                                        @endforeach
                                        <td class="border px-2 py-1">{{ $subject['total'] }}</td>
                                        <td class="border px-2 py-1">{{ number_format($subject['average'],2) }}</td>
                                        <td class="border px-2 py-1">{{ $subject['highest'] }}</td>

                                        {{-- Calculate Lowest and Position here --}}
                                        

                                        @php
                                            // $lowestScoreStudent = array_search(min($subject['total']), $subject['scores']);
                                        @endphp
                                        <td class="border px-2 py-1">{{ $subject['lowest'] }}</td> 
                                        {{-- <td class="border px-2 py-1">{{ $subject['position']  }}</td> --}}
                                    
                                        <td class="border px-2 py-1">{{ $subject['grade'] }}</td>
                                        <td class="border px-2 py-1">{{ $subject['remark'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="teacher_comment">
                         {{-- Generate the average of all total divided by the number of subjects --}}
                            <br><hr><br>
                             {{-- Key to grades... --}}
                            <div class="key-to-grades w-full">
                                @php
                                    $grade_systems = App\Models\GradingSystem::find($record->grading_system_id);
                                    $grading_system = $grade_systems->grading_system;

                                    $usb = App\Models\StudentSkillBehaviour::with('scores.category')
                                        ->where('student_id', $studentId)
                                        ->whereHas('skillBehaviour', function ($q) use ($record) {
                                            $q->where('result_root_id', $record->id);
                                        })
                                        ->first();

                                    $skills = [];
                                    $behaviours = [];

                                    if ($usb) {
                                        foreach ($usb->scores as $score) {
                                            if ($score->category->type === 'skill') {
                                                $skills[] = $score;
                                            } elseif ($score->category->type === 'behavior') {
                                                $behaviours[] = $score;
                                            }
                                        }
                                    }
                                @endphp
                                <strong>Key to Grades:</strong> 
                                @foreach ($grading_system as $grade)
                                {{ $grade['min_score'] }} - {{ $grade['max_score'] }} = {{ $grade['grade'] }} 
                                @if (!$loop->last) || @endif
                            @endforeach
                            </div>
                            {{-- Remarks Table --}}
                              <table  class="w-full">
                                <thead >
                                    <tr class="table-head">
                                      <th style="text-align:center;" colspan="2">Remarks/Comments</th>    
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Total Score</td>
                                        <td>{{ array_sum(array_column($studentData['subjects'], 'total')) }}</td>    
                                    </tr>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Average</td>
                                        <td>{{ number_format(array_sum(array_column($studentData['subjects'], 'total')) / count($studentData['subjects']), 2) }}</td>

                                    </tr>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">HOD's Remarks</td>
                                        <td>
                                            @php
                                                $overallAverage = round(array_sum(array_column($studentData['subjects'], 'total')) / count($studentData['subjects']), 2);
                                                if ($overallAverage >= 90) {
                                                    $comments = ['Excellent result!', 'Outstanding result!', 'Super performance!'];
                                                    $comment = $comments[array_rand($comments)];
                                                } elseif ($overallAverage >= 80) {
                                                    $comments = ['Very good result!', 'Keep it up!', 'Keep up your brilliance!'];
                                                    $comment = $comments[array_rand($comments)];
                                                } elseif ($overallAverage >= 70) {
                                                    $comments = ['Good result!', 'Keep it up!', 'Try harder next time!'];
                                                    $comment = $comments[array_rand($comments)];
                                                } elseif ($overallAverage >= 60) {
                                                    $comments = ['Fair result!', 'You can do more next time!', 'Keep it up!'];
                                                    $comment = $comments[array_rand($comments)];
                                                } else {
                                                    $comments = ['Needs improvement!', 'Try harder next time!', 'Stay focused in your subjects!'];
                                                    $comment = $comments[array_rand($comments)];
                                                }
                                            @endphp
                                            {{ $comment }}
                                        </td>     
                                    </tr>

                                </tbody>
                            </table>

                            {{-- Skills and Behaviours Section --}}
@if($usb)
    <div style="margin-top: 40px;">
        <h3 style="text-align:center; font-weight:bold; font-size:1.2rem; margin-bottom:10px;">
            SKILLS AND BEHAVIOURS
        </h3>

        <div style="display:flex; justify-content:space-between; gap:30px;">
            {{-- Skills Table --}}
            <table class="border-collapse border border-gray-400 text-center w-1/2">
                <thead style="background:#f0f0f0;">
                    <tr>
                        <th class="border px-2 py-1 text-left">SKILLS (1-5)</th>
                        @for ($i = 5; $i >= 1; $i--)
                            <th class="border px-2 py-1">{{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($skills as $s)
                        <tr>
                            <td class="border px-2 py-1 text-left">{{ $s->category->name }}</td>
                            @for ($i = 5; $i >= 1; $i--)
                                <td class="border px-2 py-1">
                                    @if($s->score == $i) ✔ @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Behaviours Table --}}
            <table class="border-collapse border border-gray-400 text-center w-1/2">
                <thead style="background:#f0f0f0;">
                    <tr>
                        <th class="border px-2 py-1 text-left">BEHAVIOURS (1-5)</th>
                        @for ($i = 5; $i >= 1; $i--)
                            <th class="border px-2 py-1">{{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($behaviours as $b)
                        <tr>
                            <td class="border px-2 py-1 text-left">{{ $b->category->name }}</td>
                            @for ($i = 5; $i >= 1; $i--)
                                <td class="border px-2 py-1">
                                    @if($b->score == $i) ✔ @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif


                            <div style="margin-top:30px;">
                                <img src="{{ Storage::url($principal_signature) }}" alt="signature" class="logo-img" style="height: 50px;">
                               
                                {{ $schoolDetails['principal_name'] }}
                                <br>
                                <b><cite>HOD</cite></b>
                                        

                            </div>

                              <br><hr><br>

                        </div>





                    </div>
                @endforeach
            </div>
        @endforeach
    @endif


    @assets
    <style>
        div.key-to-grades{
            background-color: #f4fa9c;
            text-align: center;
            padding: 10px;
        }
        table{
            margin-top:20px;
            }
        .table-head{
            background-color: rgb(5, 107, 5) !important;
            color:#fff;
            width: 100% !important;
        }
       
        tr:nth-child(even){
            background-color: #fff;
            border:none !important;
        }
        tr:nth-child(odd){
            background-color: #d2eafd;
        }
        table.skills-behaviours td, 
table.skills-behaviours th {
    padding: 6px;
    text-align: center;
}
table.skills-behaviours th:first-child,
table.skills-behaviours td:first-child {
    text-align: left;
}

    </style>

    {{-- Tab Switching Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.tab-toggle');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    // Hide all tab contents
                    contents.forEach(content => content.classList.add('hidden'));
                    // Remove active class from tabs
                    tabs.forEach(t => t.classList.remove('bg-gray-300'));

                    // Show the selected tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.remove('hidden');
                    this.classList.add('bg-gray-300');
                });
            });

            // Trigger the first tab by default
            if (tabs.length > 0) {
                tabs[0].click();
            }
        });
    </script>

    @endassets
</x-filament-panels::page>
