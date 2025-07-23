import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '../user.service';  // Asegurate de que la ruta sea correcta
import { Router } from '@angular/router';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface ThemeConfig {
  background: string;
  cardColor: string;
  textColor: string;
  accentColor: string;
  borderRadius: string;
}

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.css'
})
export class ProfileComponent implements OnInit {

  userNameWeb = '';
  email = '';
  nombre = '';
  apellido = '';
  avatar = '';
  fechaNacimiento = '';
  bio = '';
  rol = '';
  fechaRegistro = '';

  apiUrl = 'http://localhost:8012/miproyecto/api';

  // Variables para configuración de temas
  showSettings = false;
  
  // Tema predeterminado (Azul Océano)
  private defaultTheme: ThemeConfig = {
    background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
    cardColor: 'rgba(255, 255, 255, 0.05)',
    textColor: '#ffffff',
    accentColor: '#64b3f4',
    borderRadius: '20px'
  };
  
  themes = [
    {
      name: 'Azul Océano (Predeterminado)',
      config: this.defaultTheme
    },
    {
      name: 'Púrpura Galáctico',
      config: {
        background: 'linear-gradient(135deg, #2d1b69 0%, #11998e 100%)',
        cardColor: 'rgba(255, 255, 255, 0.08)',
        textColor: '#ffffff',
        accentColor: '#a29bfe',
        borderRadius: '20px'
      }
    },
    {
      name: 'Verde Bosque',
      config: {
        background: 'linear-gradient(135deg, #134e5e 0%, #71b280 100%)',
        cardColor: 'rgba(255, 255, 255, 0.07)',
        textColor: '#ffffff',
        accentColor: '#55efc4',
        borderRadius: '20px'
      }
    },
    {
      name: 'Naranja Atardecer',
      config: {
        background: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%)',
        cardColor: 'rgba(0, 0, 0, 0.1)',
        textColor: '#2d3436',
        accentColor: '#fd79a8',
        borderRadius: '20px'
      }
    },
    {
      name: 'Oscuro Carbón',
      config: {
        background: 'linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 50%, #2d2d2d 100%)',
        cardColor: 'rgba(255, 255, 255, 0.03)',
        textColor: '#ffffff',
        accentColor: '#00d2d3',
        borderRadius: '20px'
      }
    },
    {
      name: 'Rosa Suave',
      config: {
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        cardColor: 'rgba(255, 255, 255, 0.1)',
        textColor: '#ffffff',
        accentColor: '#fd79a8',
        borderRadius: '20px'
      }
    }
  ];

  currentTheme: ThemeConfig = this.defaultTheme;

  constructor(private userService: UserService, private router: Router) {}

  ngOnInit(): void {
    // Primero aplicar el tema predeterminado
    this.initializeDefaultTheme();
    
    // Luego cargar el tema guardado (si existe)
    this.cargarTemaGuardado();
    
    // Finalmente cargar los datos del usuario
    this.cargarUsuario();
  }

  /**
   * Inicializa el tema predeterminado al cargar el componente
   */
  private initializeDefaultTheme(): void {
    this.currentTheme = { ...this.defaultTheme };
    this.applyTheme();
  }

  cargarUsuario(): void {
    this.userService.getUsuario().subscribe({
      next: (res) => {
        if (!res) return;

        console.log('Datos recibidos (profile):', res);

        this.userNameWeb = res.user_nameweb || '';
        this.email = res.email || '';
        this.nombre = res.nombre || '';
        this.apellido = res.apellido || '';
        this.avatar = res.avatar || '';
        this.fechaNacimiento = res.fecha_nacimiento || '';
        this.bio = res.bio || '';
        this.rol = res.rol || '';
        this.fechaRegistro = res.fecha_registro || '';
      },
      error: (err) => {
        console.error('Error al obtener datos del usuario (profile):', err);
      }
    });
  }

  irCambiarContrasena() {
    this.router.navigate(['/password-recup']);
  }

  formatFechaLarga(fecha: string): string {
    if (!fecha) return '';
    const fechaDate = new Date(fecha);
    return format(fechaDate, "EEEE, d 'de' MMMM 'de' yyyy", { locale: es });
  }

  irEditarPerfil() {
    this.router.navigate(['/registrarte'], { queryParams: { edit: 'true' } });
  }

  // Nuevas funciones para configuración
  viewSettings() {
    this.showSettings = true;
  }

  closeSettings() {
    this.showSettings = false;
  }

  selectTheme(themeConfig: ThemeConfig) {
    this.currentTheme = { ...themeConfig }; // Crear una copia del objeto
    this.applyTheme();
    this.guardarTema();
  }

  applyTheme() {
    // Asegurar que el tema se aplique correctamente
    setTimeout(() => {
      const body = document.body;
      body.style.background = this.currentTheme.background;
      
      // Aplicar variables CSS personalizadas
      document.documentElement.style.setProperty('--card-color', this.currentTheme.cardColor);
      document.documentElement.style.setProperty('--text-color', this.currentTheme.textColor);
      document.documentElement.style.setProperty('--accent-color', this.currentTheme.accentColor);
      document.documentElement.style.setProperty('--border-radius', this.currentTheme.borderRadius);
      
      console.log('Tema aplicado:', this.currentTheme);
    }, 0);
  }

  guardarTema() {
    try {
      // Guardamos el tema en memoria
      (window as any).currentTheme = this.currentTheme;
      console.log('Tema guardado:', this.currentTheme);
    } catch (error) {
      console.error('Error al guardar tema:', error);
    }
  }

  cargarTemaGuardado() {
    try {
      const savedTheme = (window as any).currentTheme;
      if (savedTheme && this.isValidTheme(savedTheme)) {
        this.currentTheme = { ...savedTheme };
        console.log('Tema cargado desde memoria:', this.currentTheme);
      } else {
        console.log('No hay tema guardado, usando tema predeterminado');
        this.currentTheme = { ...this.defaultTheme };
      }
      this.applyTheme();
    } catch (error) {
      console.error('Error al cargar tema guardado:', error);
      // En caso de error, usar tema predeterminado
      this.currentTheme = { ...this.defaultTheme };
      this.applyTheme();
    }
  }

  /**
   * Valida si un objeto tiene la estructura correcta de un tema
   */
  private isValidTheme(theme: any): theme is ThemeConfig {
    return theme &&
           typeof theme.background === 'string' &&
           typeof theme.cardColor === 'string' &&
           typeof theme.textColor === 'string' &&
           typeof theme.accentColor === 'string' &&
           typeof theme.borderRadius === 'string';
  }

  resetToDefault() {
    this.selectTheme(this.defaultTheme);
    console.log('Tema restablecido al predeterminado');
  }

  /**
   * Método para limpiar el tema guardado (útil para debugging)
   */
  clearSavedTheme() {
    delete (window as any).currentTheme;
    this.initializeDefaultTheme();
    console.log('Tema guardado eliminado, aplicando predeterminado');
  }
}