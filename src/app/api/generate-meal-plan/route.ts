import { NextRequest, NextResponse } from 'next/server'
import { generateMealPlanWithHuggingFace } from '@/lib/huggingface-client'

interface MealPlanRequest {
  height: number
  weight: number
  age: number
  sex: string
  goal: string
  timeline: number
  goalWeight?: number
  exerciseIntensity: string
  dailyActivityLevel: string
  dietaryPreferences: string[]
  foodAllergies: string[]
  foodIntolerances: string[]
  macronutrientRatio: string
  mealsPerDay: number
  bmr: number
  tdee: number
  dailyCalories: number
  proteinGrams: number
  carbGrams: number
  fatGrams: number
  waterIntake: number
}

export async function POST(request: NextRequest) {
  try {
    const body: MealPlanRequest = await request.json()
    
    // Generate meal plan using Hugging Face AI
    const mealPlanData = await generateMealPlanWithHuggingFace(body)

    return NextResponse.json({
      mealPlan: mealPlanData,
      success: true
    })

  } catch (error) {
    console.error('Error generating meal plan:', error)
    return NextResponse.json(
      { error: 'Failed to generate meal plan' },
      { status: 500 }
    )
  }
}