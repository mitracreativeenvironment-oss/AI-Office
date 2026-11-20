import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css';

// Make Three.js a page-global so components using (window as any).THREE work.
import * as THREE from 'three';
(window as any).THREE = THREE;

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>
);
