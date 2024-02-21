import React from 'react';
import ReactDOM from 'react-dom';
import './App.css'; // O CSS deve estar neste arquivo ou importado aqui

// Header Component
const Header = () => (
  <header className="dashboard-header">
    <h1>Dashboard Title</h1>
  </header>
);

// Sidebar Component
const Sidebar = () => (
  <aside className="dashboard-sidebar">
    <nav>
      <ul>
        <li>Link 1</li>
        <li>Link 2</li>
        <li>Link 3</li>
        {/* Adicione mais links conforme necessário */}
      </ul>
    </nav>
  </aside>
);

// Main Content Component
const MainContent = () => (
  <main className="dashboard-main">
    <section>
      <h2>Seção 1</h2>
      <p>Conteúdo da Seção 1</p>
    </section>
    <section>
      <h2>Seção 2</h2>
      <p>Conteúdo da Seção 2</p>
    </section>
    {/* Adicione mais seções conforme necessário */}
  </main>
);

// Footer Component
const Footer = () => (
  <footer className="dashboard-footer">
    <p>&copy; {new Date().getFullYear()} Sua Empresa. Todos os direitos reservados.</p>
  </footer>
);

// App Component
const App = () => (
  <div className="dashboard">
    <Header />
    <div className="dashboard-body">
      <Sidebar />
      <MainContent />
    </div>
    <Footer />
  </div>
);

// Render the App
ReactDOM.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
  document.getElementById('root')
);

export default App;
