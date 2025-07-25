'use client'

import { useState } from 'react'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { calculateBMR, calculateTDEE, calculateDailyCalories, calculateMacros, calculateWaterIntake } from '@/lib/calculations'
import { generatePDFReport } from '@/lib/pdf-generator'
import { Target, TrendingUp, Brain } from 'lucide-react'

const dietFormSchema = z.object({
  // Basic Info
  height: z.number().min(100).max(250),
  weight: z.number().min(30).max(300),
  age: z.number().min(13).max(100),
  sex: z.enum(['male', 'female']),
  
  // Goals
  goal: z.enum(['lose_weight', 'build_muscle', 'athletic_performance', 'body_recomposition', 'improve_health']),
  timeline: z.number().min(1).max(104), // 1-104 weeks (2 years)
  goalWeight: z.number().min(30).max(300).optional(),
  
  // Activity
  exerciseIntensity: z.enum(['low', 'moderate', 'high', 'very_high']),
  dailyActivityLevel: z.enum(['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extremely_active']),
  
  // Preferences
  dietaryPreferences: z.array(z.string()),
  foodAllergies: z.array(z.string()),
  foodIntolerances: z.array(z.string()),
  
  // Macros
  macronutrientRatio: z.enum(['balanced', 'high_protein', 'low_carb', 'high_carb']),
  mealsPerDay: z.number().min(1).max(8),
})

type DietFormData = z.infer<typeof dietFormSchema>

export default function DietCalculatorForm() {
  const [step, setStep] = useState(1)
  const [isLoading, setIsLoading] = useState(false)
  const [results, setResults] = useState<any>(null)

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    control,
    formState: { errors },
  } = useForm<DietFormData>({
    resolver: zodResolver(dietFormSchema),
    defaultValues: {
      dietaryPreferences: [],
      foodAllergies: [],
      foodIntolerances: [],
      mealsPerDay: 3,
    },
  })

  const watchedGoal = watch('goal')
  const watchedDietaryPreferences = watch('dietaryPreferences') || []
  const watchedFoodAllergies = watch('foodAllergies') || []
  const watchedFoodIntolerances = watch('foodIntolerances') || []

  const onSubmit = async (data: DietFormData) => {
    setIsLoading(true)
    
    try {
      // Calculate nutrition metrics
      const bmr = calculateBMR(data.height, data.weight, data.age, data.sex)
      const tdee = calculateTDEE(bmr, data.dailyActivityLevel, data.exerciseIntensity)
      const dailyCalories = calculateDailyCalories(tdee, data.goal, data.weight, data.goalWeight, data.timeline)
      const macros = calculateMacros(dailyCalories, data.macronutrientRatio, data.goal)
      const waterIntake = calculateWaterIntake(data.weight, data.exerciseIntensity)

      const calculationData = {
        ...data,
        bmr,
        tdee,
        dailyCalories,
        proteinGrams: macros.protein,
        carbGrams: macros.carbs,
        fatGrams: macros.fat,
        waterIntake,
      }

      // Generate AI meal recommendations
      const mealPlanResponse = await fetch('/api/generate-meal-plan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(calculationData),
      })

      if (!mealPlanResponse.ok) {
        throw new Error('Failed to generate meal plan')
      }

      const mealPlanData = await mealPlanResponse.json()
      
      setResults({
        ...calculationData,
        ...mealPlanData,
      })
      
      setStep(5) // Results step
    } catch (error) {
      console.error('Error calculating diet plan:', error)
      alert('Failed to generate diet plan. Please try again.')
    } finally {
      setIsLoading(false)
    }
  }

  const toggleArrayValue = (array: string[], value: string, setter: (arr: string[]) => void) => {
    if (array.includes(value)) {
      setter(array.filter(item => item !== value))
    } else {
      setter([...array, value])
    }
  }

  const nextStep = () => setStep(prev => Math.min(prev + 1, 4))
  const prevStep = () => setStep(prev => Math.max(prev - 1, 1))

  if (results) {
    return (
      <div className="max-w-6xl mx-auto animate-fade-in-up">
        <div className="glass rounded-3xl p-8 md:p-12">
          <div className="text-center mb-12">
            <div className="inline-flex items-center gap-2 bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 px-6 py-3 rounded-full text-sm font-medium mb-6">
              <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
              Plan Generated Successfully
            </div>
            <h2 className="text-4xl md:text-5xl font-bold mb-4">
              <span className="gradient-text">Your Personalized</span>
              <br />
              <span className="text-slate-800 dark:text-slate-100">Diet Plan</span>
            </h2>
            <p className="text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">
              AI-generated nutrition plan tailored specifically for your goals and lifestyle
            </p>
          </div>
        
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <div className="glass rounded-2xl p-6 card-hover">
              <div className="flex items-center gap-3 mb-6">
                <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                  <Target className="w-6 h-6 text-white" />
                </div>
                <h3 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Nutrition Targets</h3>
              </div>
              
              <div className="space-y-4">
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">Daily Calories</span>
                  <span className="font-bold text-blue-600 dark:text-blue-400">{Math.round(results.dailyCalories)}</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">Protein</span>
                  <span className="font-bold text-purple-600 dark:text-purple-400">{Math.round(results.proteinGrams)}g</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">Carbs</span>
                  <span className="font-bold text-green-600 dark:text-green-400">{Math.round(results.carbGrams)}g</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">Fat</span>
                  <span className="font-bold text-orange-600 dark:text-orange-400">{Math.round(results.fatGrams)}g</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">Water</span>
                  <span className="font-bold text-cyan-600 dark:text-cyan-400">{Math.round(results.waterIntake)}ml</span>
                </div>
              </div>
            </div>
            
            <div className="glass rounded-2xl p-6 card-hover">
              <div className="flex items-center gap-3 mb-6">
                <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-white" />
                </div>
                <h3 className="text-2xl font-bold text-slate-800 dark:text-slate-100">Metabolic Info</h3>
              </div>
              
              <div className="space-y-4">
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">BMR (Basal Metabolic Rate)</span>
                  <span className="font-bold text-indigo-600 dark:text-indigo-400">{Math.round(results.bmr)}</span>
                </div>
                <div className="flex justify-between items-center p-3 bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl">
                  <span className="font-medium text-slate-700 dark:text-slate-300">TDEE (Total Daily Energy)</span>
                  <span className="font-bold text-pink-600 dark:text-pink-400">{Math.round(results.tdee)}</span>
                </div>
              </div>
            </div>
          </div>

          {results.mealPlan && (
            <div className="glass rounded-2xl p-6 mb-12 card-hover">
              <div className="flex items-center gap-3 mb-6">
                <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                  <Brain className="w-6 h-6 text-white" />
                </div>
                <h3 className="text-2xl font-bold text-slate-800 dark:text-slate-100">AI-Generated Meal Plan</h3>
              </div>
              
              {results.mealPlan.weeklyPlan?.[0]?.meals && (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {results.mealPlan.weeklyPlan[0].meals.map((meal: any, index: number) => (
                    <div key={index} className="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                      <h4 className="font-bold text-slate-800 dark:text-slate-100 mb-2">{meal.name}</h4>
                      <p className="text-sm text-slate-600 dark:text-slate-300 mb-3">{meal.food}</p>
                      <div className="grid grid-cols-2 gap-2 text-xs">
                        <span className="bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-2 py-1 rounded-lg">{meal.calories} cal</span>
                        <span className="bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 px-2 py-1 rounded-lg">{meal.protein}g protein</span>
                        <span className="bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-2 py-1 rounded-lg">{meal.carbs}g carbs</span>
                        <span className="bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 px-2 py-1 rounded-lg">{meal.fat}g fat</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          <div className="flex flex-wrap justify-center gap-4">
            <Button 
              onClick={() => generatePDFReport({ ...results, mealPlan: results.mealPlan })} 
              variant="success"
              size="lg"
              className="min-w-[200px]"
            >
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Download PDF Report
            </Button>
            <Button onClick={() => window.print()} variant="outline" size="lg">
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Print Report
            </Button>
            <Button onClick={() => setStep(1)} variant="outline" size="lg">
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Start Over
            </Button>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-4xl mx-auto animate-scale-in">
      <div className="glass rounded-3xl p-8 md:p-12">
        <div className="text-center mb-12">
          <h1 className="text-3xl md:text-4xl font-bold mb-8">
            <span className="gradient-text">Diet Calculator</span>
          </h1>
          
          <div className="flex justify-between items-center max-w-md mx-auto mb-8">
            {[1, 2, 3, 4].map((stepNumber) => (
              <div key={stepNumber} className="flex flex-col items-center">
                <div
                  className={`flex items-center justify-center w-12 h-12 rounded-2xl font-bold transition-all duration-300 ${
                    stepNumber <= step 
                      ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg scale-110' 
                      : 'bg-white/60 text-slate-400 shadow-md'
                  }`}
                >
                  {stepNumber}
                </div>
                <div className="h-1 w-16 mt-3 rounded-full bg-gradient-to-r from-indigo-200 to-purple-200 overflow-hidden">
                  <div 
                    className={`h-full bg-gradient-to-r from-indigo-600 to-purple-600 transition-all duration-500 ${
                      stepNumber <= step ? 'w-full' : 'w-0'
                    }`}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        {step === 1 && (
          <div className="space-y-8 animate-fade-in-up">
            <div className="text-center">
              <h2 className="text-2xl md:text-3xl font-bold text-slate-800 dark:text-slate-100 mb-3">Basic Information</h2>
              <p className="text-slate-600 dark:text-slate-300">Tell us about yourself to get started</p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-2">
                <Label htmlFor="height" className="text-sm font-semibold text-slate-700 dark:text-slate-300">Height (cm)</Label>
                <Input
                  id="height"
                  type="number"
                  {...register('height', { valueAsNumber: true })}
                  placeholder="170"
                  className="text-center"
                />
                {errors.height && <p className="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                  </svg>
                  {errors.height.message}
                </p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="weight" className="text-sm font-semibold text-slate-700 dark:text-slate-300">Weight (kg)</Label>
                <Input
                  id="weight"
                  type="number"
                  {...register('weight', { valueAsNumber: true })}
                  placeholder="70"
                  className="text-center"
                />
                {errors.weight && <p className="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                  </svg>
                  {errors.weight.message}
                </p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="age" className="text-sm font-semibold text-slate-700 dark:text-slate-300">Age</Label>
                <Input
                  id="age"
                  type="number"
                  {...register('age', { valueAsNumber: true })}
                  placeholder="25"
                  className="text-center"
                />
                {errors.age && <p className="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                  </svg>
                  {errors.age.message}
                </p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="sex" className="text-sm font-semibold text-slate-700 dark:text-slate-300">Sex</Label>
                <Controller
                  name="sex"
                  control={control}
                  render={({ field }) => (
                    <Select onValueChange={field.onChange} value={field.value}>
                      <SelectTrigger className="h-12">
                        <SelectValue placeholder="Select sex" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="male">Male</SelectItem>
                        <SelectItem value="female">Female</SelectItem>
                      </SelectContent>
                    </Select>
                  )}
                />
                {errors.sex && <p className="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                  </svg>
                  {errors.sex.message}
                </p>}
              </div>
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="space-y-6">
            <h2 className="text-2xl font-semibold mb-4">Goals & Timeline</h2>
            
            <div>
              <Label htmlFor="goal">Primary Goal</Label>
              <Controller
                name="goal"
                control={control}
                render={({ field }) => (
                  <Select onValueChange={field.onChange} value={field.value}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select your goal" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="lose_weight">Lose Weight</SelectItem>
                      <SelectItem value="build_muscle">Build Muscle</SelectItem>
                      <SelectItem value="athletic_performance">Athletic Performance</SelectItem>
                      <SelectItem value="body_recomposition">Body Recomposition</SelectItem>
                      <SelectItem value="improve_health">Improve Health</SelectItem>
                    </SelectContent>
                  </Select>
                )}
              />
              {errors.goal && <p className="text-red-500 text-sm">{errors.goal.message}</p>}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="timeline">Timeline (weeks)</Label>
                <Input
                  id="timeline"
                  type="number"
                  {...register('timeline', { valueAsNumber: true })}
                  placeholder="12"
                />
                {errors.timeline && <p className="text-red-500 text-sm">{errors.timeline.message}</p>}
              </div>
              
              {watchedGoal === 'lose_weight' && (
                <div>
                  <Label htmlFor="goalWeight">Goal Weight (kg)</Label>
                  <Input
                    id="goalWeight"
                    type="number"
                    {...register('goalWeight', { valueAsNumber: true })}
                    placeholder="65"
                  />
                  {errors.goalWeight && <p className="text-red-500 text-sm">{errors.goalWeight.message}</p>}
                </div>
              )}
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="space-y-6">
            <h2 className="text-2xl font-semibold mb-4">Activity Level</h2>
            
            <div>
              <Label htmlFor="dailyActivityLevel">Daily Activity Level</Label>
              <Controller
                name="dailyActivityLevel"
                control={control}
                render={({ field }) => (
                  <Select onValueChange={field.onChange} value={field.value}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select activity level" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="sedentary">Sedentary (desk job, no exercise)</SelectItem>
                      <SelectItem value="lightly_active">Lightly Active (light exercise 1-3 days/week)</SelectItem>
                      <SelectItem value="moderately_active">Moderately Active (moderate exercise 3-5 days/week)</SelectItem>
                      <SelectItem value="very_active">Very Active (hard exercise 6-7 days/week)</SelectItem>
                      <SelectItem value="extremely_active">Extremely Active (very hard exercise, physical job)</SelectItem>
                    </SelectContent>
                  </Select>
                )}
              />
              {errors.dailyActivityLevel && <p className="text-red-500 text-sm">{errors.dailyActivityLevel.message}</p>}
            </div>

            <div>
              <Label htmlFor="exerciseIntensity">Exercise Intensity</Label>
              <Controller
                name="exerciseIntensity"
                control={control}
                render={({ field }) => (
                  <Select onValueChange={field.onChange} value={field.value}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select exercise intensity" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">Low Intensity</SelectItem>
                      <SelectItem value="moderate">Moderate Intensity</SelectItem>
                      <SelectItem value="high">High Intensity</SelectItem>
                      <SelectItem value="very_high">Very High Intensity</SelectItem>
                    </SelectContent>
                  </Select>
                )}
              />
              {errors.exerciseIntensity && <p className="text-red-500 text-sm">{errors.exerciseIntensity.message}</p>}
            </div>
          </div>
        )}

        {step === 4 && (
          <div className="space-y-6">
            <h2 className="text-2xl font-semibold mb-4">Dietary Preferences</h2>
            
            <div>
              <Label>Dietary Preferences</Label>
              <div className="grid grid-cols-2 gap-2 mt-2">
                {['vegetarian', 'vegan', 'keto', 'paleo', 'mediterranean', 'low_carb', 'high_protein', 'gluten_free'].map((pref) => (
                  <label key={pref} className="flex items-center space-x-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={watchedDietaryPreferences.includes(pref)}
                      onChange={() => toggleArrayValue(watchedDietaryPreferences, pref, (arr) => setValue('dietaryPreferences', arr))}
                      className="rounded"
                    />
                    <span className="capitalize">{pref.replace('_', ' ')}</span>
                  </label>
                ))}
              </div>
            </div>

            <div>
              <Label>Food Allergies</Label>
              <div className="grid grid-cols-2 gap-2 mt-2">
                {['nuts', 'dairy', 'eggs', 'shellfish', 'soy', 'wheat', 'fish', 'sesame'].map((allergy) => (
                  <label key={allergy} className="flex items-center space-x-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={watchedFoodAllergies.includes(allergy)}
                      onChange={() => toggleArrayValue(watchedFoodAllergies, allergy, (arr) => setValue('foodAllergies', arr))}
                      className="rounded"
                    />
                    <span className="capitalize">{allergy}</span>
                  </label>
                ))}
              </div>
            </div>

            <div>
              <Label>Food Intolerances</Label>
              <div className="grid grid-cols-2 gap-2 mt-2">
                {['lactose', 'gluten', 'fructose', 'histamine', 'fodmap'].map((intolerance) => (
                  <label key={intolerance} className="flex items-center space-x-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={watchedFoodIntolerances.includes(intolerance)}
                      onChange={() => toggleArrayValue(watchedFoodIntolerances, intolerance, (arr) => setValue('foodIntolerances', arr))}
                      className="rounded"
                    />
                    <span className="capitalize">{intolerance.replace('_', ' ')}</span>
                  </label>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="macronutrientRatio">Macronutrient Ratio</Label>
                <Controller
                  name="macronutrientRatio"
                  control={control}
                  render={({ field }) => (
                    <Select onValueChange={field.onChange} value={field.value}>
                      <SelectTrigger>
                        <SelectValue placeholder="Select macro ratio" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="balanced">Balanced (30/40/30)</SelectItem>
                        <SelectItem value="high_protein">High Protein (40/30/30)</SelectItem>
                        <SelectItem value="low_carb">Low Carb (35/15/50)</SelectItem>
                        <SelectItem value="high_carb">High Carb (20/60/20)</SelectItem>
                      </SelectContent>
                    </Select>
                  )}
                />
                {errors.macronutrientRatio && <p className="text-red-500 text-sm">{errors.macronutrientRatio.message}</p>}
              </div>

              <div>
                <Label htmlFor="mealsPerDay">Meals Per Day</Label>
                <Input
                  id="mealsPerDay"
                  type="number"
                  {...register('mealsPerDay', { valueAsNumber: true })}
                  placeholder="3"
                  min="1"
                  max="8"
                />
                {errors.mealsPerDay && <p className="text-red-500 text-sm">{errors.mealsPerDay.message}</p>}
              </div>
            </div>
          </div>
        )}

        <div className="flex justify-between items-center pt-8">
          {step > 1 ? (
            <Button type="button" onClick={prevStep} variant="outline" size="lg">
              <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
              Previous
            </Button>
          ) : (
            <div></div>
          )}
          
          {step < 4 ? (
            <Button type="button" onClick={nextStep} size="lg">
              Next
              <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </Button>
          ) : (
            <Button type="submit" disabled={isLoading} size="lg" className="min-w-[180px]">
              {isLoading ? (
                <>
                  <div className="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                  Generating Plan...
                </>
              ) : (
                <>
                  <Brain className="w-5 h-5 mr-2" />
                  Generate Diet Plan
                </>
              )}
            </Button>
          )}
        </div>
      </form>
      </div>
    </div>
  )
}