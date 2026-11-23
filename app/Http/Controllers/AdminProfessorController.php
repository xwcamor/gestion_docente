<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AdminProfessorController extends Controller {
 public function index(){ $professors=User::with('professor')->orderBy('name')->get(); return view('admin.professors.index',compact('professors')); }
 public function store(Request $request){ $data=$request->validate(['full_name'=>'required|string|max:255','email'=>'required|email|unique:users,email','password'=>'required|string|min:10','institutional_id'=>'nullable|string|max:80','school'=>'nullable|string|max:255','career'=>'nullable|string|max:255']); $user=User::create(['name'=>$data['full_name'],'email'=>$data['email'],'password'=>Hash::make($data['password']),'role'=>'profesor']); $user->professor()->create(['institutional_id'=>$data['institutional_id']??null,'full_name'=>$data['full_name'],'school'=>$data['school']??null,'career'=>$data['career']??null]); return back()->with('success','Cuenta docente creada.'); }
}
