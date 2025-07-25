import DietCalculatorForm from '@/components/DietCalculatorForm'
import ClientOnly from '@/components/ClientOnly'
import { Sparkles, Brain, Target, TrendingUp } from 'lucide-react'

export default function Home() {
  return (
    <div className="min-h-screen relative overflow-hidden">
      {/* Background decorative elements */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 -right-40 w-80 h-80 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-float"></div>
        <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-float" style={{animationDelay: '2s'}}></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-green-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-float" style={{animationDelay: '4s'}}></div>
      </div>

      <div className="container mx-auto px-4 py-12 relative z-10">
        {/* Hero Section */}
        <header className="text-center mb-16 animate-fade-in-up">
          <div className="inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 px-4 py-2 rounded-full text-sm font-medium mb-6 animate-pulse-soft">
            <Sparkles className="w-4 h-4" />
            AI-Powered Nutrition Science
          </div>
          
          <h1 className="text-5xl md:text-7xl font-bold mb-6 leading-tight">
            <span className="gradient-text">Professional</span>
            <br />
            <span className="text-slate-800 dark:text-slate-100">Diet Calculator</span>
          </h1>
          
          <p className="text-xl md:text-2xl text-slate-600 dark:text-slate-300 max-w-3xl mx-auto mb-8 leading-relaxed">
            Transform your health journey with personalized nutrition recommendations, 
            AI-generated meal plans, and comprehensive progress tracking.
          </p>

          {/* Feature badges */}
          <div className="flex flex-wrap justify-center gap-4 mb-12">
            <div className="flex items-center gap-2 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium border border-white/20 hover:scale-105 transition-transform">
              <Brain className="w-4 h-4 text-purple-600" />
              AI Meal Planning
            </div>
            <div className="flex items-center gap-2 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium border border-white/20 hover:scale-105 transition-transform">
              <Target className="w-4 h-4 text-green-600" />
              Personalized Goals
            </div>
            <div className="flex items-center gap-2 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-medium border border-white/20 hover:scale-105 transition-transform">
              <TrendingUp className="w-4 h-4 text-blue-600" />
              Progress Tracking
            </div>
          </div>
        </header>

        {/* Calculator Section */}
        <div className="animate-scale-in">
          <ClientOnly fallback={
            <div className="flex flex-col items-center justify-center py-16">
              <div className="animate-spin rounded-full h-16 w-16 border-4 border-indigo-200 border-t-indigo-600 mb-4"></div>
              <p className="text-lg text-slate-600 dark:text-slate-300">Loading your personalized calculator...</p>
            </div>
          }>
            <DietCalculatorForm />
          </ClientOnly>
        </div>

        {/* Stats Section */}
        <section className="mt-24 grid grid-cols-1 md:grid-cols-3 gap-8 animate-fade-in-up">
          <div className="text-center glass rounded-2xl p-8 card-hover">
            <div className="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl mx-auto mb-4 flex items-center justify-center">
              <Brain className="w-8 h-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-2">AI-Powered</h3>
            <p className="text-slate-600 dark:text-slate-300">Advanced algorithms create personalized meal plans based on your unique needs and preferences.</p>
          </div>

          <div className="text-center glass rounded-2xl p-8 card-hover">
            <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl mx-auto mb-4 flex items-center justify-center">
              <Target className="w-8 h-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-2">Precision Tracking</h3>
            <p className="text-slate-600 dark:text-slate-300">Accurate BMR and TDEE calculations using scientifically proven formulas for optimal results.</p>
          </div>

          <div className="text-center glass rounded-2xl p-8 card-hover">
            <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl mx-auto mb-4 flex items-center justify-center">
              <TrendingUp className="w-8 h-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-2">Real Progress</h3>
            <p className="text-slate-600 dark:text-slate-300">Comprehensive PDF reports and progress tracking to keep you motivated and on track.</p>
          </div>
        </section>

        {/* Footer */}
        <footer className="mt-24 text-center">
          <div className="glass rounded-2xl p-8 inline-block">
            <p className="text-slate-600 dark:text-slate-300 mb-2">&copy; 2025 Professional Diet Calculator</p>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Created with modern web technologies and AI-powered nutrition science
            </p>
          </div>
        </footer>
      </div>
    </div>
  );
}
