@extends('adminlte::page')
@section('title', 'Izin Pulang Siswa')
@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)
@section('css')
<style>
 .permit-hero{background:linear-gradient(135deg,#0d6efd,#0b5ed7);color:#fff;border-radius:.6rem;padding:1.25rem 1.5rem;margin-bottom:1rem;box-shadow:0 .25rem .75rem rgba(13,110,253,.18)}
 .permit-hero h1{font-size:1.35rem;margin:0}.permit-hero p{opacity:.85;margin:.25rem 0 0}
 .filter-card,.form-card{border:0;border-radius:.6rem;box-shadow:0 .15rem .65rem rgba(0,0,0,.07)}
 .form-card .card-header{background:#fff;border-bottom:1px solid #edf0f4}.form-label{font-size:.78rem;font-weight:600;color:#495057}
 #permitTable thead th{white-space:nowrap;background:#f6f8fb;border-top:0}.parent-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:.45rem;padding:.7rem .85rem;font-size:.85rem}
 .modal-content{border:0;border-radius:.65rem}.modal-header{background:#f6f8fb;border-bottom:0}.modal-footer{border-top:0}
</style>
@stop
@section('content_header')<div class="permit-hero"><h1><i class="fas fa-home mr-2"></i>Izin Pulang Siswa</h1><p>Kelola izin pulang, perbarui data orang tua, dan cetak surat izin.</p></div>@stop
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@php
    $studentOptions = $students->map(function ($student) {
        return [
            'id' => $student->id, 'name' => $student->nama_lengkap, 'nis' => $student->nis,
            'majorId' => $student->classroom?->major_id,
            'major' => $student->classroom?->major?->nama_jurusan ?? 'Tanpa Jurusan',
            'classId' => $student->classroom_id, 'className' => $student->classroom?->nama_kelas,
            'parent' => $student->nama_orang_tua_wali, 'phone' => $student->no_hp_orang_tua,
        ];
    })->values();
@endphp
<div class="card filter-card mb-3"><div class="card-body"><div class="form-row align-items-end">
 <div class="form-group col-md-4 mb-2"><label class="form-label">Jurusan</label><select id="majorFilter" class="form-control form-control-sm"><option value="">Semua jurusan</option></select></div>
 <div class="form-group col-md-4 mb-2"><label class="form-label">Kelas</label><select id="classFilter" class="form-control form-control-sm" disabled><option value="">Semua kelas</option></select></div>
 <div class="form-group col-md-4 mb-2"><label class="form-label">Nama Siswa</label><select id="studentFilter" class="form-control form-control-sm" disabled><option value="">Semua siswa</option></select></div>
</div></div></div>
<div class="card form-card mb-3"><div class="card-header py-3 d-flex justify-content-between align-items-center"><strong><i class="fas fa-plus-circle text-primary mr-1"></i> Buat Izin Pulang</strong><button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#permitModal"><i class="fas fa-plus mr-1"></i>Input Baru</button></div></div>
<div class="card form-card"><div class="card-body"><div class="table-responsive"><table id="permitTable" class="table table-bordered table-striped table-sm w-100"><thead><tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Orang Tua / No. HP</th><th>Tanggal</th><th>Jenis</th><th>Jam</th><th>Alasan</th><th>Aksi</th></tr></thead></table></div></div></div>

<div class="modal fade" id="permitModal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="permitForm" method="POST" action="{{ route('tu.mid-class-permits.store') }}">@csrf<div id="methodField"></div><div class="modal-header"><h5 class="modal-title"><i class="fas fa-file-signature mr-1"></i><span id="modalTitle">Buat Izin Pulang</span></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
<div class="form-row"><div class="form-group col-md-6"><label class="form-label">Jurusan</label><select id="formMajor" class="form-control form-control-sm"><option value="">Pilih jurusan</option></select></div><div class="form-group col-md-6"><label class="form-label">Kelas</label><select id="formClass" class="form-control form-control-sm" disabled><option value="">Pilih kelas</option></select></div></div>
<div class="form-group"><label class="form-label">Nama Siswa</label><select name="student_id" id="formStudent" class="form-control form-control-sm" required disabled><option value="">Pilih siswa</option></select></div><div class="parent-box mb-3" id="parentInfo">Pilih siswa untuk memuat data orang tua.</div>
<div class="form-row"><div class="form-group col-md-3"><label class="form-label">Tanggal</label><input name="tanggal" id="formDate" type="date" class="form-control form-control-sm" required max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}"></div><div class="form-group col-md-3"><label class="form-label">Jenis</label><select name="tipe_izin" id="formType" class="form-control form-control-sm"><option value="penuh">Pulang penuh</option><option value="sementara">Sementara</option></select></div><div class="form-group col-md-3"><label class="form-label">Jam keluar</label><input name="jam_keluar" id="formOut" type="time" class="form-control form-control-sm" value="{{ now()->format('H:i') }}" required></div><div class="form-group col-md-3"><label class="form-label">Jam kembali</label><input name="jam_kembali" id="formBack" type="time" class="form-control form-control-sm"></div></div>
<div class="form-group"><label class="form-label">Nama orang tua/wali</label><input name="nama_orang_tua_wali" id="formParent" class="form-control form-control-sm" required></div><div class="form-group"><label class="form-label">No. HP orang tua</label><input name="no_hp_orang_tua" id="formPhone" class="form-control form-control-sm" required></div><div class="form-group"><label class="form-label">Alasan</label><textarea name="alasan" id="formReason" class="form-control form-control-sm" rows="2" required></textarea></div><div class="form-group mb-0"><label class="form-label">Catatan</label><textarea name="catatan" id="formNote" class="form-control form-control-sm" rows="2"></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-light border btn-sm" data-dismiss="modal">Batal</button><button class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Simpan</button></div></form></div></div></div>
@stop
@section('js')<script>
$(function(){
 const students=@json($studentOptions);
 const unique=(a,k)=>a.filter((v,i,x)=>x.findIndex(y=>y[k]===v[k])===i); const dataUrl='{{ route('tu.mid-class-permits.data') }}'; let table;
 function fill(select,rows,placeholder){select.empty().append(new Option(placeholder,''));rows.forEach(x=>select.append(new Option(x.text,x.value)));}
 function majors(select){fill(select,unique(students,'majorId').map(x=>({value:x.majorId,text:x.major})),'Pilih jurusan');}
 function classes(select,major){fill(select,unique(students.filter(x=>String(x.majorId)===String(major)),'classId').map(x=>({value:x.classId,text:x.className})),'Pilih kelas');select.prop('disabled',!major);}
 function studentOptions(select,classId){fill(select,students.filter(x=>String(x.classId)===String(classId)).map(x=>({value:x.id,text:x.name+' — '+x.nis})),'Pilih siswa');select.prop('disabled',!classId);}
 majors($('#majorFilter'));majors($('#formMajor'));
 $('#majorFilter').on('change',function(){classes($('#classFilter'),this.value);$('#studentFilter').empty().append(new Option('Semua siswa','')).prop('disabled',true);table.ajax.reload();});
 $('#classFilter').on('change',function(){studentOptions($('#studentFilter'),this.value).prepend(new Option('Semua siswa',''));table.ajax.reload();});$('#studentFilter').on('change',()=>table.ajax.reload());
 $('#formMajor').on('change',function(){classes($('#formClass'),this.value);$('#formStudent').empty().append(new Option('Pilih siswa','')).prop('disabled',true);});$('#formClass').on('change',function(){studentOptions($('#formStudent'),this.value);});
 function formStudent(s){$('#parentInfo').html('<b>Orang tua:</b> '+(s.parent||'-')+' &nbsp; <b>No. HP:</b> '+(s.phone||'-'));$('#formParent').val(s.parent||'');$('#formPhone').val(s.phone||'');}
 $('#formStudent').on('change',function(){const s=students.find(x=>String(x.id)===String(this.value));if(s)formStudent(s);});
 table=$('#permitTable').DataTable({processing:true,serverSide:true,ajax:{url:dataUrl,data:d=>{d.major_id=$('#majorFilter').val();d.classroom_id=$('#classFilter').val();d.student_id=$('#studentFilter').val();}},columns:[{data:null,orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'student'},{data:'classroom'},{data:null,render:d=>(d.parent||'-')+'<br><small class="text-muted">'+(d.phone||'-')+'</small>'},{data:'tanggal'},{data:'tipe_izin',render:v=>v==='penuh'?'<span class="badge badge-warning">Pulang penuh</span>':'<span class="badge badge-info">Sementara</span>'},{data:null,render:d=>d.jam_keluar+(d.jam_kembali?' - '+d.jam_kembali:'')},{data:'alasan'},{data:null,orderable:false,searchable:false,render:d=>'<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary edit-permit" data-row=\''+JSON.stringify(d).replace(/'/g,'&apos;')+'\'><i class="fas fa-edit"></i></button><button class="btn btn-outline-secondary print-permit" data-url="'+d.print_url+'"><i class="fas fa-print"></i></button><button class="btn btn-outline-danger delete-permit" data-id="'+d.id+'"><i class="fas fa-trash"></i></button></div>'}]});
 $('#permitTable').on('click','.delete-permit',function(){const id=$(this).data('id');Swal.fire({title:'Hapus izin pulang?',icon:'warning',showCancelButton:true,confirmButtonText:'Hapus',cancelButtonText:'Batal'}).then(r=>{if(r.isConfirmed)$.ajax({url:'/tu/izin-pulang/'+id,type:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).done(()=>{Swal.fire('Berhasil','Izin dihapus.','success');table.ajax.reload(null,false);}).fail(()=>Swal.fire('Gagal','Izin tidak dapat dihapus.','error'));});});
 $('#permitTable').on('click','.print-permit',function(){window.open($(this).data('url'),'_blank');});
 $('#permitTable').on('click','.edit-permit',function(){const d=table.row($(this).closest('tr')).data();$('#modalTitle').text('Edit Izin Pulang');$('#permitForm').attr('action','/tu/izin-pulang/'+d.id);$('#methodField').html('<input type="hidden" name="_method" value="PUT">');$('#formDate').val(d.tanggal);$('#formType').val(d.tipe_izin);$('#formOut').val(d.jam_keluar);$('#formBack').val(d.jam_kembali);$('#formReason').val(d.alasan);$('#formNote').val(d.catatan||'');$('#formParent').val(d.parent||'');$('#formPhone').val(d.phone||'');$('#formStudent').val(d.student_id).trigger('change');$('#formMajor').val(students.find(x=>x.id===d.student_id)?.majorId).trigger('change');setTimeout(()=>{$('#formClass').val(d.classroom_id).trigger('change');setTimeout(()=>$('#formStudent').val(d.student_id).trigger('change'),100)},100);$('#permitModal').modal('show');});
 $('#permitForm').on('submit',function(e){if($('#methodField').html()){e.preventDefault();$.ajax({url:$(this).attr('action'),type:'POST',data:$(this).serialize()}).done(()=>{ $('#permitModal').modal('hide');Swal.fire('Berhasil','Izin diperbarui.','success');table.ajax.reload(null,false);}).fail(()=>Swal.fire('Gagal','Periksa isian form.','error'));}});
 $('#permitModal').on('hidden.bs.modal',function(){$('#permitForm')[0].reset();$('#methodField').empty();$('#modalTitle').text('Buat Izin Pulang');});
});
</script>@stop

