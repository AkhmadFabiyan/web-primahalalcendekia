@php
    $progress = \App\Modules\Workflows\Services\WorkflowProgressService::forProject($getRecord());
@endphp

<div class="fi-in-workflow-progress bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 p-4">
    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4">Progress Sinkronisasi Workflow</h3>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-200">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">Workflow</th>
                    <th scope="col" class="px-4 py-3 font-medium">Status</th>
                    <th scope="col" class="px-4 py-3 font-medium text-right">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Workflow A &mdash; Entry</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-md bg-{{ $progress['entry']['completed'] ? 'success' : 'gray' }}-50 px-2 py-1 text-xs font-medium text-{{ $progress['entry']['completed'] ? 'success' : 'gray' }}-700 ring-1 ring-inset ring-{{ $progress['entry']['completed'] ? 'success' : 'gray' }}-600/20 dark:bg-{{ $progress['entry']['completed'] ? 'success' : 'gray' }}-400/10 dark:text-{{ $progress['entry']['completed'] ? 'success' : 'gray' }}-400 dark:ring-{{ $progress['entry']['completed'] ? 'success' : 'gray' }}-400/20">
                            {{ $progress['entry']['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ $progress['entry']['percentage'] }}%</td>
                </tr>
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Workflow B &mdash; Audit</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-md bg-{{ $progress['auditor']['completed'] ? 'success' : 'gray' }}-50 px-2 py-1 text-xs font-medium text-{{ $progress['auditor']['completed'] ? 'success' : 'gray' }}-700 ring-1 ring-inset ring-{{ $progress['auditor']['completed'] ? 'success' : 'gray' }}-600/20 dark:bg-{{ $progress['auditor']['completed'] ? 'success' : 'gray' }}-400/10 dark:text-{{ $progress['auditor']['completed'] ? 'success' : 'gray' }}-400 dark:ring-{{ $progress['auditor']['completed'] ? 'success' : 'gray' }}-400/20">
                            {{ $progress['auditor']['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ $progress['auditor']['percentage'] }}%</td>
                </tr>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Kesiapan tahap berikutnya</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $progress['gate']['completed_workflows'] }} dari {{ $progress['gate']['required_workflows'] }} workflow selesai
                    </td>
                    <td class="px-4 py-3 text-right font-bold {{ $progress['gate']['ready'] ? 'text-success-600 dark:text-success-400' : 'text-gray-500' }}">
                        {{ $progress['gate']['ready'] ? 'Siap' : 'Belum Siap' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
