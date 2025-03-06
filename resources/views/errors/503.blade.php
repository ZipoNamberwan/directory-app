@extends('layouts.app')

@section('content')
<main class="main-content  mt-0">
    <section>
        <div class="page-header min-vh-100">
            <div class="container">
                <div class="row">
                    <div
                        class="col-12 h-100 my-auto pe-0 position-absolute top-0 end-0 text-center justify-content-center flex-column">
                        <div class="position-relative bg-gradient-primary h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center overflow-hidden"
                            style="background-image: url('{{ asset('img/error.jpg') }}'); background-size: cover; background-position: center;">
                            <span class="mask bg-gradient-primary opacity-6"></span>
                            <h1 class="mt-5 text-white font-weight-bolder position-relative">"Ken Dedes Sedang Maintenance Bersama Ken Arok"</h1>
                            <h4 class="text-white position-relative">Kami akan kembali secepatnya🙏</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection