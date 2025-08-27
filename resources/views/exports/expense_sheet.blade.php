@php
  $monthName = \Carbon\Carbon::create($sheet->period_year, $sheet->period_month, 1)->format('F Y');
@endphp
<table>
  <tr>
    <td><strong>PT: Universal Trade Services</strong></td>
  </tr>
  <tr>
    <td>Period: {{ $monthName }}</td>
  </tr>
  <tr><td>&nbsp;</td></tr>

  <tr>
    <td><strong>Beginning Balance:</strong></td>
    <td>{{ is_null($begin) ? '-' : number_format($begin,2) }}</td>
  </tr>
  <tr><td>&nbsp;</td></tr>

  <tr>
    <th>No</th>
    <th>Date</th>
    <th>Description</th>
    <th>Doc number</th>
    <th>Debit</th>
    <th>Credit</th>
    <th>Amount</th>
    <th>Remarks</th>
  </tr>

  @forelse($rows as $i => $r)
    <tr>
      <td>{{ $i+1 }}</td>
      <td>{{ optional($r->date)->format('Y-m-d') }}</td>
      <td>{{ $r->description }}</td>
      <td>{{ $r->doc_number }}</td>
      <td>{{ $r->debit }}</td>
      <td>{{ $r->credit }}</td>
      <td>{{ $r->amount }}</td>
      <td>{{ $r->remarks }}</td>
    </tr>
  @empty
    <tr><td colspan="8">No rows</td></tr>
  @endforelse

  <tr><td colspan="8">&nbsp;</td></tr>
  <tr>
    <td colspan="4" align="right"><strong>Total</strong></td>
    <td>{{ number_format($totalDebit,2) }}</td>
    <td>{{ number_format($totalCredit,2) }}</td>
    <td>{{ number_format($totalAmount,2) }}</td>
    <td></td>
  </tr>

  <tr><td colspan="8">&nbsp;</td></tr>
  <tr>
    <td><strong>Beginning Balance</strong></td>
    <td>{{ is_null($begin) ? '-' : number_format($begin,2) }}</td>
  </tr>
  <tr>
    <td><strong>Mutation</strong></td>
    <td>{{ number_format($mutation,2) }}</td>
  </tr>
  <tr>
    <td><strong>Ending Balance</strong></td>
    <td>{{ is_null($ending) ? '-' : number_format($ending,2) }}</td>
  </tr>
</table>
