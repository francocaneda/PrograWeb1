import { Component } from '@angular/core';
import { RouterLink, RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-regis-user',
  standalone: true,
  imports: [RouterLink, RouterModule, FormsModule, CommonModule],
  templateUrl: './regis-user.component.html',
  styleUrls: ['./regis-user.component.css']
})
export class RegisUserComponent {
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

  constructor(private http: HttpClient, private router: Router) { }

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
    if (this.clave !== this.reclave) {
      setTimeout(() => { this.loading = false; }, 1500);
      Swal.fire({ icon: 'error', text: 'Las contraseñas no coinciden' });
      return;
    }

    if (form.invalid) return;

    this.loading = true;

    const formData = new FormData();
    formData.append('userName', this.userNameWeb);
    formData.append('email', this.email);
    formData.append('password', this.clave);
    formData.append('nombre', this.nombre);
    formData.append('apellido', this.apellido);
    formData.append('fechaNacimiento', this.fechaNacimiento);
    formData.append('bio', this.bio);

    if (this.selectedFile) {
      formData.append('avatar', this.selectedFile);
    }

    this.http.post('http://localhost:8012/miproyecto/api/index.php?comando=usuarios', formData)
      .subscribe({
        next: (response) => {
          setTimeout(() => {
            this.loading = false;
            form.resetForm();
            Swal.fire({
              icon: 'success',
              title: '¡Registro exitoso!',
              showConfirmButton: false,
              timer: 2500
            });
            this.router.navigate(['/loginscreen']);
          }, 1500);
        },
        error: (err) => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al registrar usuario',
          });
          setTimeout(() => { this.loading = false; }, 1000);
          console.error(err);
        }
      });
  }
}
