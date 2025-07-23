import { Component, OnInit } from '@angular/core';
import { RouterLink, RouterModule, Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';
import { UserService } from '../user.service';

@Component({
  selector: 'app-regis-user',
  standalone: true,
  imports: [RouterLink, RouterModule, FormsModule, CommonModule],
  templateUrl: './regis-user.component.html',
  styleUrls: ['./regis-user.component.css']
})
export class RegisUserComponent implements OnInit {
  userNameWeb = '';
  email = '';
  clave = '';
  reclave = '';
  nombre = '';
  apellido = '';
  fechaNacimiento = '';
  bio = '';
  avatar = '';

  selectedFile: File | null = null;
  selectedFileName: string | null = null;
  previewUrl: string | ArrayBuffer | null = null;

  loading = false;
  showPassword = false;
  showRePassword = false;

  isEditMode = false;
  id: number = 0;

  constructor(
    private http: HttpClient,
    private router: Router,
    private route: ActivatedRoute,
    private userService: UserService
  ) { }

  ngOnInit(): void {
    this.route.queryParams.subscribe(params => {
      this.isEditMode = params['edit'] === 'true';

      if (this.isEditMode) {
        this.userService.getUsuario().subscribe(user => {
          if (user) {
            this.id = user.id;
            this.userNameWeb = user.user_nameweb;
            this.email = user.email;
            this.nombre = user.nombre;
            this.apellido = user.apellido;
            this.fechaNacimiento = user.fecha_nacimiento;
            this.bio = user.bio;
            this.avatar = user.avatar;
          }
        });
      }
    });
  }

  togglePasswordVisibility() {
    this.showPassword = !this.showPassword;
  }

  toggleRePasswordVisibility() {
    this.showRePassword = !this.showRePassword;
  }

  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      this.selectedFile = file;
      this.selectedFileName = file.name;

      const reader = new FileReader();
      reader.onload = () => {
        this.previewUrl = reader.result;
      };
      reader.readAsDataURL(file);
    }
  }

  onSubmit(form: any) {
    if (!this.isEditMode && this.clave !== this.reclave) {
      setTimeout(() => { this.loading = false; }, 1500);
      Swal.fire({ icon: 'error', text: 'Las contraseñas no coinciden' });
      return;
    }

    if (form.invalid) return;

    this.loading = true;

    const formData = new FormData();

    if (this.isEditMode) {
      formData.append('id', this.id?.toString() ?? '');  // Enviar el id
    }
    formData.append('userName', this.userNameWeb);        // userName con mayúscula N
    formData.append('email', this.email);
    formData.append('fechaNacimiento', this.fechaNacimiento);
    formData.append('bio', this.bio);
    if (!this.isEditMode && this.clave) {  // Solo enviar clave en registro
      formData.append('password', this.clave);
      formData.append('nombre', this.nombre);
      formData.append('apellido', this.apellido);
    }


    if (this.selectedFile) {
      formData.append('avatar', this.selectedFile);
    }

    if (this.isEditMode) {
      this.http.post('http://localhost:8012/miproyecto/api/index.php?comando=updateUsuario', formData)
        .subscribe({
          next: (response) => {
            this.loading = false;
            // Aquí cargas nuevamente el usuario actualizado
            this.userService.getUsuario().subscribe(user => {
              if (user) {
                // Actualizas los campos del componente o el BehaviorSubject en UserService
                this.userNameWeb = user.user_nameweb;
                this.email = user.email;
                this.nombre = user.nombre;
                this.apellido = user.apellido;
                this.fechaNacimiento = user.fecha_nacimiento;
                this.bio = user.bio;
                this.avatar = user.avatar;
              }
            });
            Swal.fire({
              icon: 'success',
              title: '¡Perfil actualizado!',
              showConfirmButton: false,
              timer: 2500
            });
            this.router.navigate(['/main-layout/index']);
          },
          error: (err) => {
            this.loading = false;
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error al actualizar el perfil',
            });
            console.error(err);
          }
        });
    } else {
      this.http.post('http://localhost:8012/miproyecto/api/index.php?comando=usuarios', formData)
        .subscribe({
          next: (response) => {
            this.loading = false;
            form.resetForm();
            Swal.fire({
              icon: 'success',
              title: '¡Registro exitoso!',
              showConfirmButton: false,
              timer: 2500
            });
            this.router.navigate(['/loginscreen']);
          },
          error: (err) => {
            this.loading = false;
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error al registrar usuario',
            });
            console.error(err);
          }
        });
    }
  }
}
