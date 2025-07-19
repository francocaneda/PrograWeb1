import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import { PostService } from '../post.service';
import { CategoryService, Categoria } from '../category.service';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import Swal from 'sweetalert2';
import { Router, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-create-post',
  standalone: true,
  imports: [CommonModule, HttpClientModule, ReactiveFormsModule, FormsModule],
  templateUrl: './create-post.component.html',
  styleUrl: './create-post.component.css'
})
export class CreatePostComponent implements OnInit {
  postForm!: FormGroup;
  categorias: Categoria[] = [];
  isLoading: boolean = true;
  categoriaPreseleccionada: string | null = null;

  constructor(
    private fb: FormBuilder,
    private postService: PostService,
    private categoryService: CategoryService,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    // Leemos el parámetro ?categoria desde la URL
    this.categoriaPreseleccionada = this.route.snapshot.queryParamMap.get('categoria');

    // Creamos el formulario con el valor precargado si existe
    this.postForm = this.fb.group({
      id_categoria: [this.categoriaPreseleccionada || '', Validators.required],
      titulo: ['', [Validators.required, Validators.minLength(10), Validators.maxLength(150)]],
      contenido: ['', Validators.maxLength(1000)]
    });

    // Cargamos las categorías desde el servicio
    this.categoryService.getCategorias().subscribe({
      next: (resp) => {
        this.categorias = resp.categorias;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error al cargar categorías', err);
        Swal.fire({
          icon: 'error',
          title: 'Error al cargar categorías',
          text: 'Ocurrió un problema al obtener las categorías.'
        });
        this.isLoading = false;
      }
    });
  }

  onSubmit(): void {
    if (this.postForm.invalid) {
      Swal.fire({
        icon: 'warning',
        title: 'Formulario incompleto',
        text: 'Por favor completa correctamente todos los campos.'
      });
      return;
    }

    this.postService.crearPost(this.postForm.value).subscribe({
      next: () => {
        Swal.fire({
          icon: 'success',
          title: '¡Publicación creada!',
          text: 'Tu post fue publicado exitosamente.',
          timer: 1500,
          showConfirmButton: false
        }).then(() => {
          this.postForm.reset();
          this.router.navigate(['/main-layout/index']);
        });
      },
      error: (err) => {
        console.error('Error al crear el post', err);
        Swal.fire({
          icon: 'error',
          title: 'Error al publicar',
          text: 'Necesitas estar logueado para publicar.'
        });
      }
    });
  }

  onCancel(): void {
    if (this.postForm.pristine) {
      this.router.navigate(['/main-layout/index']);
      return;
    }

    Swal.fire({
      title: '¿Estás seguro de cancelar?',
      text: 'Perderás el contenido que hayas escrito.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, salir',
      cancelButtonText: 'Continuar escribiendo'
    }).then((result) => {
      if (result.isConfirmed) {
        this.postForm.reset();
        this.router.navigate(['/main-layout/index']);
      }
    });
  }
}
