interface HuggingFaceResponse {
  generated_text?: string;
  error?: string;
}

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

export async function generateMealPlanWithHuggingFace(data: MealPlanRequest) {
  const apiKey = process.env.HUGGINGFACE_API_KEY
  
  if (!apiKey) {
    console.warn('Hugging Face API key not found, using mock data')
    return generateMockMealPlan(data)
  }

  const prompt = createMealPlanPrompt(data)
  
  try {
    // Using Hugging Face's text generation model - trying multiple models for better compatibility
    const models = [
      'microsoft/DialoGPT-medium',
      'google/flan-t5-base', 
      'facebook/blenderbot-400M-distill',
      'microsoft/DialoGPT-small'
    ]
    
    let response
    let lastError
    
    for (const model of models) {
      try {
        response = await fetch(
          `https://api-inference.huggingface.co/models/${model}`,
          {
            headers: {
              'Authorization': `Bearer ${apiKey}`,
              'Content-Type': 'application/json',
            },
            method: 'POST',
            body: JSON.stringify({
              inputs: prompt,
              parameters: {
                max_new_tokens: 1000,
                temperature: 0.8,
                do_sample: true,
                return_full_text: false
              }
            }),
          }
        )
        
        if (response.ok) {
          break
        } else {
          lastError = await response.text()
          console.log(`Model ${model} failed: ${response.status} - ${lastError}`)
        }
      } catch (error) {
        console.log(`Model ${model} error:`, error)
        lastError = error
      }
    }

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const result = await response.json() as HuggingFaceResponse[]
    
    if (result[0]?.error) {
      throw new Error(result[0].error)
    }

    const generatedText = result[0]?.generated_text || ''
    
    // Parse the generated text and structure it
    return parseHuggingFaceResponse(generatedText, data)
    
  } catch (error) {
    console.error('Hugging Face API error:', error)
    console.log('Falling back to mock data')
    return generateMockMealPlan(data)
  }
}

function createMealPlanPrompt(data: MealPlanRequest): string {
  const bmi = data.weight / ((data.height / 100) ** 2)
  const bmiCategory = bmi < 18.5 ? 'underweight' : bmi < 25 ? 'normal' : bmi < 30 ? 'overweight' : 'obese'
  const isDeficit = data.goal === 'lose_weight'
  const isSurplus = data.goal === 'build_muscle'
  const hasRestrictions = data.foodAllergies.length > 0 || data.foodIntolerances.length > 0
  
  return `You are a certified nutritionist and meal planning expert. Create a comprehensive, personalized meal plan based on scientific nutrition principles.

CLIENT PROFILE:
- Demographics: ${data.age}yr ${data.sex === 'male' ? 'male' : 'female'}, ${data.height}cm, ${data.weight}kg (BMI: ${bmi.toFixed(1)} - ${bmiCategory})
- Primary Goal: ${data.goal.replace('_', ' ').toUpperCase()}
- Timeline: ${data.timeline} weeks ${data.goalWeight ? `(target: ${data.goalWeight}kg)` : ''}
- Activity: ${data.dailyActivityLevel.replace('_', ' ')} lifestyle, ${data.exerciseIntensity.replace('_', ' ')} exercise intensity
- Meal Frequency: ${data.mealsPerDay} meals/day
- Macro Strategy: ${data.macronutrientRatio.replace('_', ' ')}

CALCULATED TARGETS:
- Daily Calories: ${Math.round(data.dailyCalories)} (${isDeficit ? 'DEFICIT' : isSurplus ? 'SURPLUS' : 'MAINTENANCE'})
- Protein: ${Math.round(data.proteinGrams)}g (${Math.round(data.proteinGrams * 4 / data.dailyCalories * 100)}%)
- Carbohydrates: ${Math.round(data.carbGrams)}g (${Math.round(data.carbGrams * 4 / data.dailyCalories * 100)}%)
- Fat: ${Math.round(data.fatGrams)}g (${Math.round(data.fatGrams * 9 / data.dailyCalories * 100)}%)
- Water: ${Math.round(data.waterIntake)}ml

DIETARY REQUIREMENTS:
- Preferences: ${data.dietaryPreferences.length > 0 ? data.dietaryPreferences.join(', ') : 'None specified'}
- Allergies: ${data.foodAllergies.length > 0 ? data.foodAllergies.join(', ') : 'None'}
- Intolerances: ${data.foodIntolerances.length > 0 ? data.foodIntolerances.join(', ') : 'None'}

EXPERT ANALYSIS REQUIRED:
Based on this profile, provide detailed nutritional recommendations that consider:
1. Metabolic needs based on age, sex, and activity level
2. Goal-specific macro timing and food choices
3. Micronutrient requirements for this demographic
4. Food synergies and anti-nutrients
5. Meal timing for optimal results
6. Cultural and practical considerations

Generate a comprehensive meal plan with:

1. SAMPLE DAILY MEALS (${data.mealsPerDay} meals):
   - Specific foods with exact portions
   - Macro breakdown per meal
   - Timing recommendations
   - Preparation methods that preserve nutrients

2. 7-DAY VARIETY SUGGESTIONS:
   - Different protein sources throughout the week
   - Seasonal vegetable rotations
   - Healthy cooking methods
   - Snack alternatives

3. FOOD CATEGORIZATION:
   PRIORITIZE (Good to Have): Foods that actively support your ${data.goal.replace('_', ' ')} goal
   NEUTRAL (Moderate): Foods that can be included in moderation
   MINIMIZE (Avoid/Limit): Foods that may hinder progress or cause issues

4. PERSONALIZED RECIPES (5 recipes):
   - Goal-specific recipes with macro-optimized ingredients
   - Consider dietary restrictions: ${hasRestrictions ? 'YES - ' + [...data.foodAllergies, ...data.foodIntolerances].join(', ') : 'None'}
   - Prep and cook times
   - Nutritional benefits explanation

5. STRATEGIC SHOPPING LIST:
   - Organized by food category
   - Quantities for weekly planning
   - Budget-friendly alternatives
   - Quality indicators to look for

6. EVIDENCE-BASED SUPPLEMENTS:
   - Based on potential gaps in this specific diet plan
   - Consider demographics and goals
   - Timing and dosage recommendations
   - Interactions and contraindications

7. MEAL PREP STRATEGY:
   - Batch cooking recommendations
   - Food storage best practices
   - Time-saving techniques
   - Make-ahead options

8. SUCCESS TIPS:
   - Hydration strategy
   - Meal timing for ${data.goal.replace('_', ' ')}
   - How to handle social situations
   - Progress monitoring suggestions

Be specific, scientific, and practical. Consider real-world constraints and provide actionable advice that a ${data.age}-year-old ${data.sex} can realistically follow.`
}

function parseHuggingFaceResponse(generatedText: string, data: MealPlanRequest) {
  console.log('AI Generated Text Preview:', generatedText.substring(0, 200))
  
  // Try to extract structured information from AI response
  const aiInsights = extractAIInsights(generatedText, data)
  
  return {
    weeklyPlan: [
      {
        day: 1,
        meals: generatePersonalizedMeals(generatedText, data, aiInsights)
      }
    ],
    recipes: generateIntelligentRecipes(generatedText, data, aiInsights),
    shoppingList: generateSmartShoppingList(data, aiInsights),
    supplements: generateEvidenceBasedSupplements(data, aiInsights),
    hydrationSchedule: generatePersonalizedHydration(data),
    mealPrepTips: generateContextualMealPrepTips(data, aiInsights),
    foodCategorization: generateFoodCategorization(data, aiInsights),
    nutritionalAnalysis: generateNutritionalAnalysis(data, aiInsights),
    aiGenerated: true,
    aiConfidence: generatedText.length > 100 ? 'high' : 'fallback',
    generatedText: generatedText.substring(0, 1000)
  }
}

function extractAIInsights(text: string, data: MealPlanRequest) {
  // Extract key insights from AI response for better recommendations
  const insights = {
    recommendedFoods: [],
    avoidFoods: [],
    keyNutrients: [],
    mealTiming: '',
    specialConsiderations: []
  }
  
  // Look for food recommendations in the text
  const foodPatterns = [
    /recommend[s]?\s+([a-zA-Z\s,]+)/gi,
    /good\s+sources?\s+of[:\s]+([a-zA-Z\s,]+)/gi,
    /include[s]?\s+([a-zA-Z\s,]+)/gi
  ]
  
  foodPatterns.forEach(pattern => {
    const matches = text.match(pattern)
    if (matches) {
      matches.forEach(match => {
        insights.recommendedFoods.push(match.toLowerCase())
      })
    }
  })
  
  // Look for foods to avoid
  const avoidPatterns = [
    /avoid[s]?\s+([a-zA-Z\s,]+)/gi,
    /limit[s]?\s+([a-zA-Z\s,]+)/gi,
    /minimize[s]?\s+([a-zA-Z\s,]+)/gi
  ]
  
  avoidPatterns.forEach(pattern => {
    const matches = text.match(pattern)
    if (matches) {
      matches.forEach(match => {
        insights.avoidFoods.push(match.toLowerCase())
      })
    }
  })
  
  return insights
}

function generateFoodCategorization(data: MealPlanRequest, aiInsights: any) {
  const bmi = data.weight / ((data.height / 100) ** 2)
  const isVegetarian = data.dietaryPreferences.includes('vegetarian') || data.dietaryPreferences.includes('vegan')
  const isVegan = data.dietaryPreferences.includes('vegan')
  const isKeto = data.dietaryPreferences.includes('keto') || data.dietaryPreferences.includes('low_carb')
  const hasNutAllergy = data.foodAllergies.includes('nuts')
  const hasDairyAllergy = data.foodAllergies.includes('dairy') || data.foodIntolerances.includes('lactose') || isVegan
  const hasGlutenIssues = data.foodAllergies.includes('gluten') || data.foodIntolerances.includes('gluten')
  
  // Goal-specific food recommendations
  const goalSpecificFoods = {
    lose_weight: {
      prioritize: ['leafy greens', 'lean protein', 'fiber-rich vegetables', 'berries', 'green tea', 'quinoa', 'salmon', 'greek yogurt', 'legumes', 'egg whites'],
      neutral: ['whole grains', 'nuts in moderation', 'lean poultry', 'low-fat dairy', 'sweet potato', 'oats', 'brown rice'],
      minimize: ['processed foods', 'sugary drinks', 'refined carbs', 'fried foods', 'alcohol', 'high-calorie snacks', 'white bread', 'candy']
    },
    build_muscle: {
      prioritize: ['lean meats', 'eggs', 'protein powder', 'greek yogurt', 'cottage cheese', 'quinoa', 'sweet potato', 'nuts', 'avocado', 'salmon'],
      neutral: ['whole grains', 'fruits', 'vegetables', 'healthy fats', 'dairy products', 'legumes'],
      minimize: ['excessive alcohol', 'processed meats', 'trans fats', 'excessive sugar', 'low-protein processed foods']
    },
    athletic_performance: {
      prioritize: ['complex carbs', 'lean protein', 'anti-inflammatory foods', 'beetroot', 'tart cherries', 'banana', 'oats', 'salmon', 'spinach'],
      neutral: ['whole grains', 'healthy fats', 'varied vegetables', 'fruits', 'nuts', 'seeds'],
      minimize: ['processed foods', 'excessive fiber before workouts', 'high-fat meals pre-exercise', 'alcohol']
    },
    body_recomposition: {
      prioritize: ['high-protein foods', 'nutrient-dense vegetables', 'complex carbs around workouts', 'lean fish', 'eggs', 'legumes'],
      neutral: ['moderate healthy fats', 'whole grains', 'fruits', 'nuts in moderation'],
      minimize: ['empty calories', 'processed snacks', 'excessive simple carbs', 'calorie-dense low-nutrition foods']
    },
    improve_health: {
      prioritize: ['colorful vegetables', 'omega-3 rich fish', 'whole grains', 'berries', 'nuts', 'olive oil', 'legumes', 'fermented foods'],
      neutral: ['lean meats', 'dairy products', 'fruits', 'herbs and spices'],
      minimize: ['processed meats', 'trans fats', 'excessive sodium', 'refined sugars', 'processed foods']
    }
  }
  
  const baseRecommendations = goalSpecificFoods[data.goal as keyof typeof goalSpecificFoods] || goalSpecificFoods.improve_health
  
  // Adjust for dietary preferences
  let prioritize = [...baseRecommendations.prioritize]
  let neutral = [...baseRecommendations.neutral]
  let minimize = [...baseRecommendations.minimize]
  
  if (isVegan) {
    prioritize = prioritize.filter(food => !['lean meats', 'eggs', 'salmon', 'greek yogurt', 'cottage cheese', 'dairy products', 'lean fish', 'lean poultry'].some(animal => food.includes(animal)))
    prioritize.push('tofu', 'tempeh', 'nutritional yeast', 'hemp seeds', 'chia seeds', 'plant-based protein powder')
  } else if (isVegetarian) {
    prioritize = prioritize.filter(food => !['lean meats', 'salmon', 'lean fish', 'lean poultry'].some(meat => food.includes(meat)))
  }
  
  if (isKeto) {
    prioritize = prioritize.filter(food => !['quinoa', 'sweet potato', 'oats', 'brown rice', 'complex carbs', 'whole grains'].some(carb => food.includes(carb)))
    neutral = neutral.filter(food => !['whole grains', 'fruits', 'legumes'].some(carb => food.includes(carb)))
    prioritize.push('avocado', 'mct oil', 'grass-fed butter', 'fatty fish', 'low-carb vegetables')
    minimize.push('grains', 'most fruits', 'legumes', 'starchy vegetables')
  }
  
  if (hasNutAllergy) {
    prioritize = prioritize.filter(food => !food.includes('nuts'))
    neutral = neutral.filter(food => !food.includes('nuts'))
    prioritize.push('seeds (sunflower, pumpkin)', 'tahini', 'coconut')
  }
  
  if (hasDairyAllergy) {
    // Remove all dairy-containing foods
    const dairyTerms = ['dairy', 'milk', 'yogurt', 'cheese', 'cottage cheese', 'greek yogurt', 'low-fat dairy']
    prioritize = prioritize.filter(food => !dairyTerms.some(dairy => food.toLowerCase().includes(dairy.toLowerCase())))
    neutral = neutral.filter(food => !dairyTerms.some(dairy => food.toLowerCase().includes(dairy.toLowerCase())))
    
    // Add dairy alternatives
    prioritize.push('plant-based milk alternatives', 'coconut yogurt', 'nutritional yeast for B12', 'calcium-fortified plant milk')
    minimize.push('all dairy products', 'milk-based foods', 'whey protein', 'casein protein')
  }
  
  if (hasGlutenIssues) {
    minimize.push('wheat', 'barley', 'rye', 'conventional oats', 'processed foods with gluten')
    prioritize.push('gluten-free grains', 'rice', 'certified gluten-free oats')
  }
  
  // Add allergy-specific recommendations
  data.foodAllergies.forEach(allergy => {
    minimize.push(`foods containing ${allergy}`, `${allergy} products`)
  })
  
  return {
    prioritize: {
      title: "Prioritize (Good to Have)",
      description: `Foods that actively support your ${data.goal.replace('_', ' ')} goal and overall health`,
      foods: prioritize,
      reasoning: `These foods are specifically chosen based on your ${data.goal.replace('_', ' ')} goal, ${data.sex} demographics, and ${data.dailyActivityLevel.replace('_', ' ')} lifestyle.`
    },
    neutral: {
      title: "Neutral (Moderate Consumption)",
      description: "Foods that can be included in moderation as part of a balanced approach",
      foods: neutral,
      reasoning: "These foods provide good nutrition but should be balanced with your primary goal foods."
    },
    minimize: {
      title: "Minimize (Avoid/Limit)",
      description: "Foods that may hinder your progress or cause health issues",
      foods: minimize,
      reasoning: `These foods may interfere with your ${data.goal.replace('_', ' ')} goal or conflict with your dietary restrictions.`
    }
  }
}

function generateNutritionalAnalysis(data: MealPlanRequest, aiInsights: any) {
  const bmi = data.weight / ((data.height / 100) ** 2)
  const proteinPerKg = data.proteinGrams / data.weight
  
  return {
    metabolicProfile: {
      bmr: Math.round(data.bmr),
      tdee: Math.round(data.tdee),
      bmi: bmi.toFixed(1),
      proteinPerKg: proteinPerKg.toFixed(1),
      analysis: `Your metabolic profile indicates ${bmi < 18.5 ? 'underweight' : bmi < 25 ? 'healthy weight' : bmi < 30 ? 'overweight' : 'obese'} status. Your protein intake of ${proteinPerKg.toFixed(1)}g/kg is ${proteinPerKg < 1.2 ? 'below optimal' : proteinPerKg > 2.0 ? 'quite high' : 'within recommended range'} for your ${data.goal.replace('_', ' ')} goal.`
    },
    macroBalance: {
      proteinPercent: Math.round(data.proteinGrams * 4 / data.dailyCalories * 100),
      carbPercent: Math.round(data.carbGrams * 4 / data.dailyCalories * 100),
      fatPercent: Math.round(data.fatGrams * 9 / data.dailyCalories * 100),
      assessment: generateMacroAssessment(data)
    },
    micronutrientFocus: generateMicronutrientRecommendations(data),
    mealTimingStrategy: generateMealTimingAdvice(data)
  }
}

function generateMacroAssessment(data: MealPlanRequest): string {
  const proteinPercent = Math.round(data.proteinGrams * 4 / data.dailyCalories * 100)
  const carbPercent = Math.round(data.carbGrams * 4 / data.dailyCalories * 100)
  const fatPercent = Math.round(data.fatGrams * 9 / data.dailyCalories * 100)
  
  let assessment = `Your macro split (${proteinPercent}% protein, ${carbPercent}% carbs, ${fatPercent}% fat) is `
  
  if (data.goal === 'build_muscle' && proteinPercent >= 25) {
    assessment += 'well-suited for muscle building with adequate protein for synthesis.'
  } else if (data.goal === 'lose_weight' && proteinPercent >= 30) {
    assessment += 'excellent for weight loss with high protein to preserve muscle mass.'
  } else if (data.macronutrientRatio === 'low_carb' && carbPercent <= 20) {
    assessment += 'aligned with low-carb principles for metabolic flexibility.'
  } else {
    assessment += 'balanced for general health and your specified goals.'
  }
  
  return assessment
}

function generateMicronutrientRecommendations(data: MealPlanRequest) {
  const age = data.age
  const sex = data.sex
  const goal = data.goal
  
  const recommendations = []
  
  if (sex === 'female' && age < 50) {
    recommendations.push('Iron-rich foods (spinach, lean meats, legumes) due to menstrual losses')
    recommendations.push('Folate sources (leafy greens, fortified grains) for reproductive health')
  }
  
  if (age > 50) {
    recommendations.push('Vitamin B12 and D3 for age-related absorption changes')
    recommendations.push('Calcium-rich foods for bone health maintenance')
  }
  
  if (goal === 'athletic_performance' || data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high') {
    recommendations.push('Antioxidant-rich foods (berries, colorful vegetables) for recovery')
    recommendations.push('Electrolyte balance through natural sources (coconut water, bananas)')
  }
  
  if (data.dietaryPreferences.includes('vegan')) {
    recommendations.push('B12 supplementation is essential for vegans')
    recommendations.push('Omega-3 from algae sources or walnuts, flax seeds')
    recommendations.push('Iron absorption enhancers (vitamin C foods with iron-rich meals)')
  }
  
  return recommendations
}

function generateMealTimingAdvice(data: MealPlanRequest): string {
  if (data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high') {
    return `For your high-intensity training: Eat carbs 1-2 hours before workouts, protein within 30 minutes after. Space your ${data.mealsPerDay} meals every ${Math.round(16 / data.mealsPerDay)} hours while awake.`
  } else if (data.goal === 'lose_weight') {
    return `For weight loss: Consider longer gaps between meals to promote fat oxidation. Your ${data.mealsPerDay} meals can be spaced 3-4 hours apart with the last meal 2-3 hours before bed.`
  } else {
    return `Spread your ${data.mealsPerDay} meals evenly throughout the day, ensuring protein at each meal for optimal muscle protein synthesis.`
  }
}

function generatePersonalizedMeals(text: string, data: MealPlanRequest, aiInsights: any) {
  // Extract meal information from generated text or use structured approach
  const mealNames = ['Breakfast', 'Lunch', 'Dinner', 'Snack 1', 'Snack 2', 'Pre-workout', 'Post-workout', 'Evening snack']
  const mealsToGenerate = Math.min(data.mealsPerDay, mealNames.length)
  
  const caloriesPerMeal = data.dailyCalories / data.mealsPerDay
  const proteinPerMeal = data.proteinGrams / data.mealsPerDay
  const carbsPerMeal = data.carbGrams / data.mealsPerDay
  const fatPerMeal = data.fatGrams / data.mealsPerDay
  
  const meals = []
  
  for (let i = 0; i < mealsToGenerate; i++) {
    const mealSuggestions = getMealSuggestions(mealNames[i], data.dietaryPreferences, data.foodAllergies)
    
    meals.push({
      name: mealNames[i],
      food: mealSuggestions.food,
      calories: Math.round(caloriesPerMeal),
      protein: Math.round(proteinPerMeal),
      carbs: Math.round(carbsPerMeal),
      fat: Math.round(fatPerMeal),
      ingredients: mealSuggestions.ingredients,
      portions: mealSuggestions.portions
    })
  }
  
  return meals
}

function getMealSuggestions(mealName: string, preferences: string[], allergies: string[]) {
  const isVegetarian = preferences.includes('vegetarian') || preferences.includes('vegan')
  const isVegan = preferences.includes('vegan')
  const isKeto = preferences.includes('keto') || preferences.includes('low_carb')
  const hasNutAllergy = allergies.includes('nuts')
  const hasDairyAllergy = allergies.includes('dairy') || isVegan
  
  const mealOptions: Record<string, any> = {
    'Breakfast': {
      food: isVegan ? 'Overnight Oats with Berries' : 
            hasDairyAllergy ? 'Chia Pudding with Plant Milk' :
            isKeto ? 'Avocado and Eggs' : 'Greek Yogurt Parfait',
      ingredients: isVegan ? ['oats', 'plant milk', 'berries', 'chia seeds'] : 
                   hasDairyAllergy ? ['chia seeds', 'coconut milk', 'berries', 'nuts'] :
                   isKeto ? ['eggs', 'avocado', 'spinach', 'olive oil'] :
                   ['greek yogurt', 'berries', 'granola', 'honey'],
      portions: hasDairyAllergy ? '3 tbsp chia seeds, 1 cup coconut milk' :
                isKeto ? '2 eggs, 1/2 avocado' : '1 cup yogurt, 1/2 cup berries'
    },
    'Lunch': {
      food: isVegetarian ? 'Quinoa Buddha Bowl' : 
            hasDairyAllergy && isKeto ? 'Chicken Avocado Salad' :
            isKeto ? 'Chicken Caesar Salad' : 'Grilled Chicken Breast',
      ingredients: isVegetarian ? ['quinoa', 'chickpeas', 'vegetables', 'tahini'] :
                   hasDairyAllergy && isKeto ? ['chicken', 'romaine', 'avocado', 'olive oil'] :
                   isKeto ? ['chicken', 'romaine', 'parmesan', 'olive oil'] :
                   ['chicken breast', 'brown rice', 'broccoli', 'olive oil'],
      portions: isKeto ? '150g chicken, 2 cups salad' : '120g protein, 1 cup grains'
    },
    'Dinner': {
      food: isVegan ? 'Lentil Curry with Vegetables' : isKeto ? 'Salmon with Asparagus' : 'Lean Beef with Sweet Potato',
      ingredients: isVegan ? ['lentils', 'coconut milk', 'vegetables', 'spices'] :
                   isKeto && hasDairyAllergy ? ['salmon', 'asparagus', 'olive oil', 'herbs'] :
                   isKeto ? ['salmon', 'asparagus', 'butter', 'herbs'] :
                   ['lean beef', 'sweet potato', 'green beans', 'herbs'],
      portions: isKeto ? '150g salmon, 200g vegetables' : '120g protein, 1 medium potato'
    }
  }
  
  return mealOptions[mealName] || {
    food: 'Balanced meal with protein and vegetables',
    ingredients: ['protein source', 'vegetables', 'healthy fats'],
    portions: 'Appropriate serving size'
  }
}

function generateIntelligentRecipes(text: string, data: MealPlanRequest, aiInsights: any) {
  const isVegetarian = data.dietaryPreferences.includes('vegetarian') || data.dietaryPreferences.includes('vegan')
  const isVegan = data.dietaryPreferences.includes('vegan')
  const isKeto = data.dietaryPreferences.includes('keto') || data.dietaryPreferences.includes('low_carb')
  const hasNutAllergy = data.foodAllergies.includes('nuts')
  const hasDairyAllergy = data.foodAllergies.includes('dairy') || isVegan
  const bmi = data.weight / ((data.height / 100) ** 2)
  
  // Goal-specific recipe collections
  const recipeDatabase = {
    lose_weight: [
      {
        name: 'Mediterranean Stuffed Bell Peppers',
        servings: 2,
        prepTime: '15 mins',
        cookTime: '35 mins',
        ingredients: isVegan ? 
          ['4 bell peppers', '1 cup quinoa', '1 can chickpeas', '1 diced tomato', '1/2 cup olive tapenade', 'fresh herbs'] :
          ['4 bell peppers', '200g lean ground turkey', '1 cup cauliflower rice', '1 diced tomato', '2 tbsp feta cheese', 'oregano'],
        instructions: [
          'Preheat oven to 375°F (190°C)',
          'Cut tops off peppers and remove seeds',
          'Mix filling ingredients in a bowl',
          'Stuff peppers with mixture and bake 30-35 minutes',
          'Serve with side salad'
        ],
        macrosPerServing: {
          calories: Math.round(data.dailyCalories * 0.35),
          protein: Math.round(data.proteinGrams * 0.4),
          carbs: Math.round(data.carbGrams * 0.25),
          fat: Math.round(data.fatGrams * 0.3)
        },
        benefits: 'High fiber, low calorie density, supports satiety for weight loss'
      },
      {
        name: 'Zucchini Noodle Protein Bowl',
        servings: 1,
        prepTime: '10 mins',
        cookTime: '8 mins',
        ingredients: isVegan ?
          ['2 large zucchini', '150g firm tofu', '1 tbsp nutritional yeast', '1 cup cherry tomatoes', '2 tbsp hemp seeds', 'basil'] :
          ['2 large zucchini', '150g grilled chicken', '1/4 cup parmesan', '1 cup cherry tomatoes', '1 tbsp pine nuts', 'basil'],
        instructions: [
          'Spiralize zucchini into noodles',
          'Sauté protein of choice with herbs',
          'Lightly cook zucchini noodles for 2-3 minutes',
          'Combine with protein and cherry tomatoes',
          'Top with nuts/seeds and fresh herbs'
        ],
        macrosPerServing: {
          calories: Math.round(data.dailyCalories * 0.3),
          protein: Math.round(data.proteinGrams * 0.35),
          carbs: Math.round(data.carbGrams * 0.15),
          fat: Math.round(data.fatGrams * 0.25)
        },
        benefits: 'Very low carb, high protein, promotes fat oxidation'
      }
    ],
    build_muscle: [
      {
        name: 'Power-Packed Overnight Oats',
        servings: 1,
        prepTime: '5 mins',
        cookTime: '0 mins (overnight)',
        ingredients: isVegan ?
          ['1 cup rolled oats', '1 scoop plant protein powder', '2 tbsp almond butter', '1 banana', '1 cup plant milk', '1 tbsp chia seeds'] :
          ['1 cup rolled oats', '1 scoop whey protein', '2 tbsp peanut butter', '1 banana', '1 cup milk', '1 tbsp ground flaxseed'],
        instructions: [
          'Mix oats, protein powder, and liquid in jar',
          'Add nut butter and mashed banana',
          'Stir in seeds and refrigerate overnight',
          'Top with extra banana slices before eating',
          'Eat within 30 minutes post-workout for optimal results'
        ],
        macrosPerServing: {
          calories: Math.round(data.dailyCalories * 0.4),
          protein: Math.round(data.proteinGrams * 0.45),
          carbs: Math.round(data.carbGrams * 0.4),
          fat: Math.round(data.fatGrams * 0.35)
        },
        benefits: 'High protein for muscle synthesis, complex carbs for sustained energy'
      },
      {
        name: 'Anabolic Chicken and Sweet Potato Stack',
        servings: 1,
        prepTime: '10 mins',
        cookTime: '25 mins',
        ingredients: isVegetarian ?
          ['200g extra-firm tofu', '1 large sweet potato', '1 cup spinach', '1/4 avocado', '2 tbsp tahini sauce'] :
          ['200g chicken breast', '1 large sweet potato', '1 cup broccoli', '1/4 avocado', '1 tbsp olive oil'],
        instructions: [
          'Bake sweet potato at 400°F for 20 minutes',
          'Season and grill protein until cooked through',
          'Steam vegetables until tender-crisp',
          'Stack ingredients with protein on top',
          'Drizzle with healthy fat source'
        ],
        macrosPerServing: {
          calories: Math.round(data.dailyCalories * 0.45),
          protein: Math.round(data.proteinGrams * 0.5),
          carbs: Math.round(data.carbGrams * 0.45),
          fat: Math.round(data.fatGrams * 0.4)
        },
        benefits: 'Complete amino acid profile, optimal carb timing for muscle growth'
      }
    ],
    athletic_performance: [
      {
        name: 'Pre-Workout Energy Balls',
        servings: 6,
        prepTime: '15 mins',
        cookTime: '0 mins',
        ingredients: hasNutAllergy ?
          ['1 cup dates', '1/2 cup sunflower seeds', '2 tbsp coconut oil', '1 tsp vanilla', 'pinch of sea salt'] :
          ['1 cup dates', '1/2 cup almonds', '2 tbsp almond butter', '1 tsp vanilla', '1 tbsp cacao powder'],
        instructions: [
          'Soak dates in warm water for 10 minutes',
          'Pulse nuts/seeds in food processor until roughly chopped',
          'Add dates and other ingredients, process until sticky',
          'Roll into 12 balls and refrigerate',
          'Eat 1-2 balls 30 minutes before training'
        ],
        macrosPerServing: {
          calories: Math.round(data.dailyCalories * 0.08),
          protein: Math.round(data.proteinGrams * 0.08),
          carbs: Math.round(data.carbGrams * 0.15),
          fat: Math.round(data.fatGrams * 0.1)
        },
        benefits: 'Quick-digesting carbs for immediate energy, portable for training'
      }
    ]
  }
  
  const goalRecipes = recipeDatabase[data.goal as keyof typeof recipeDatabase] || recipeDatabase.lose_weight
  return goalRecipes.slice(0, 3) // Return top 3 recipes for the specific goal
}

function generateSmartShoppingList(data: MealPlanRequest, aiInsights: any) {
  const isVegetarian = data.dietaryPreferences.includes('vegetarian') || data.dietaryPreferences.includes('vegan')
  const isVegan = data.dietaryPreferences.includes('vegan')
  const isKeto = data.dietaryPreferences.includes('keto') || data.dietaryPreferences.includes('low_carb')
  const hasDairyAllergy = data.foodAllergies.includes('dairy') || isVegan
  const hasNutAllergy = data.foodAllergies.includes('nuts')
  const bmi = data.weight / ((data.height / 100) ** 2)
  
  // Intelligent quantity calculation based on TDEE and goals
  const weeklyProteinNeeds = (data.proteinGrams * 7) / 4 // Convert to weekly portions
  const weeklyCaloriesBudget = data.dailyCalories * 7
  
  const list = {
    proteins: {
      title: 'PROTEIN SOURCES',
      items: [],
      weeklyTarget: `${Math.round(weeklyProteinNeeds * 4)}g protein/week`,
      tips: data.goal === 'build_muscle' ? 
        'Focus on complete proteins with all essential amino acids' :
        'Lean proteins help maintain muscle during weight loss'
    },
    vegetables: {
      title: 'VEGETABLES & GREENS',
      items: [],
      weeklyTarget: '2-3kg mixed vegetables for micronutrients',
      tips: 'Aim for 5+ different colors throughout the week for diverse phytonutrients'
    },
    grains: {
      title: 'COMPLEX CARBOHYDRATES',
      items: [],
      weeklyTarget: isKeto ? 'Minimal carbs (<50g/day)' : `${Math.round(data.carbGrams * 7 / 100)}kg carbs/week`,
      tips: isKeto ? 'Focus on fiber-rich, low-net-carb options' : 'Time carbs around your workouts for best results'
    },
    fats: {
      title: 'HEALTHY FATS',
      items: [],
      weeklyTarget: `${Math.round(data.fatGrams * 7)}g healthy fats/week`,
      tips: 'Essential for hormone production and vitamin absorption'
    },
    pantry: {
      title: 'PANTRY ESSENTIALS',
      items: [],
      weeklyTarget: 'Stock up on flavor enhancers and cooking basics',
      tips: 'Quality spices and condiments make healthy eating more enjoyable'
    }
  }
  
  // Smart protein selection based on goals and preferences
  if (isVegan) {
    list.proteins.items = [
      'Organic firm tofu (800g) - versatile protein base',
      'Tempeh (400g) - fermented, complete protein',
      'Red lentils (1kg) - quick-cooking, high-protein legume', 
      'Chickpeas (800g dried or 3 cans) - fiber + protein',
      `${data.goal === 'build_muscle' ? 'Plant protein powder (1kg)' : 'Plant protein powder (500g)'} - post-workout convenience`,
      'Hemp seeds (250g) - omega-3 rich protein boost',
      'Nutritional yeast (100g) - B12 + cheesy flavor'
    ]
  } else if (isVegetarian) {
    if (hasDairyAllergy) {
      list.proteins.items = [
        'Plant-based protein powder (1kg) - dairy-free complete protein',
        'Free-range eggs (18 pack) - complete amino acid profile', 
        'Quinoa (500g) - complete plant protein',
        'Hemp hearts (300g) - complete protein + omega-3s',
        'Nutritional yeast (200g) - B12 + protein boost',
        'Pea protein powder (500g) - easily digestible'
      ]
    } else {
      list.proteins.items = [
        `Greek yogurt (1kg) - ${Math.round(1000 * 0.1)}g protein, probiotic benefits`,
        'Cottage cheese (500g) - casein protein for sustained release',
        'Free-range eggs (18 pack) - complete amino acid profile',
        'Quinoa (500g) - complete plant protein',
        data.goal === 'build_muscle' ? 'Whey protein powder (1kg)' : 'Protein powder (500g)',
        'Paneer or firm cheese (300g) - calcium + protein'
      ]
    }
  } else {
    if (hasDairyAllergy) {
      list.proteins.items = [
        `Chicken breast (${data.goal === 'build_muscle' ? '1.5kg' : '1kg'}) - lean, versatile protein`,
        'Wild-caught salmon (600g) - omega-3 + high-quality protein',
        'Free-range eggs (12-18 pack) - bioavailable nutrients',
        'Plant-based protein powder (500g) - dairy-free option',
        data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high' ? 'Lean ground turkey (500g)' : 'Canned tuna (4 cans)',
        'Hemp hearts (200g) - complete protein alternative'
      ].filter(Boolean)
    } else {
      list.proteins.items = [
        `Chicken breast (${data.goal === 'build_muscle' ? '1.5kg' : '1kg'}) - lean, versatile protein`,
        'Wild-caught salmon (600g) - omega-3 + high-quality protein',
        'Free-range eggs (12-18 pack) - bioavailable nutrients',
        'Greek yogurt (500g) - probiotics + protein',
        data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high' ? 'Lean ground turkey (500g)' : 'Canned tuna (4 cans)',
        data.goal === 'build_muscle' ? 'Whey protein powder (1kg)' : null
      ].filter(Boolean)
    }
  }
  
  // Goal-specific vegetable selection
  if (data.goal === 'lose_weight') {
    list.vegetables.items = [
      'Baby spinach (400g) - iron, low-calorie volume',
      'Broccoli crowns (1kg) - fiber, supports detoxification', 
      'Bell peppers (6 mixed colors) - vitamin C, antioxidants',
      'Zucchini (4 large) - low-calorie pasta substitute',
      'Cauliflower (2 heads) - versatile low-carb base',
      'Cucumber (4 large) - hydrating, virtually zero calories',
      'Cherry tomatoes (500g) - lycopene, natural flavor enhancer',
      'Leafy salad mix (300g) - nutrient density, satiety'
    ]
  } else if (data.goal === 'build_muscle') {
    list.vegetables.items = [
      'Sweet potatoes (1kg) - complex carbs for muscle glycogen',
      'Spinach (300g) - iron for oxygen transport',
      'Broccoli (500g) - vitamin K for bone health',
      'Asparagus (400g) - supports recovery',
      'Beets (3 medium) - nitrates for blood flow',
      'Carrots (500g) - beta-carotene, natural sweetness',
      'Red bell peppers (4) - vitamin C for collagen synthesis'
    ]
  } else {
    list.vegetables.items = [
      'Mixed leafy greens (400g) - diverse micronutrients',
      'Broccoli (500g) - cruciferous benefits',
      'Colorful bell peppers (4) - antioxidant variety',
      'Carrots (500g) - fiber + beta-carotene',
      'Zucchini (2 large) - versatile, low-calorie',
      'Cherry tomatoes (300g) - lycopene, flavor'
    ]
  }
  
  // Carbohydrate sources based on preferences and goals
  if (isKeto) {
    list.grains.items = [
      'Cauliflower rice (1kg frozen) - rice substitute',
      'Almond flour (500g) - baking substitute',
      'Coconut flour (250g) - high-fiber, low-carb',
      'Shirataki noodles (4 packs) - near-zero carb pasta'
    ]
  } else if (data.goal === 'athletic_performance') {
    list.grains.items = [
      'Steel-cut oats (1kg) - sustained energy release',
      'Quinoa (500g) - complete protein + complex carbs',
      'Brown rice (1kg) - easily digestible post-workout',
      'Sweet potatoes (1kg) - natural electrolytes',
      'Whole grain pasta (500g) - carb loading option'
    ]
  } else {
    list.grains.items = [
      'Quinoa (500g) - complete protein grain',
      'Steel-cut oats (500g) - fiber + sustained energy',
      'Brown rice (500g) - whole grain staple',
      data.goal === 'build_muscle' ? 'Sweet potatoes (1kg)' : 'Sweet potatoes (500g)'
    ].filter(Boolean)
  }
  
  // Healthy fats selection
  if (hasNutAllergy) {
    list.fats.items = [
      'Avocados (6) - monounsaturated fats, fiber',
      'Extra virgin olive oil (500ml) - cooking + dressing',
      'Coconut oil (250ml) - medium-chain triglycerides',
      'Sunflower seeds (200g) - vitamin E, nut-free protein',
      'Pumpkin seeds (150g) - magnesium, zinc',
      'Tahini (200g) - sesame-based, nut-free spread'
    ]
  } else {
    list.fats.items = [
      'Avocados (4-6) - heart-healthy monounsaturated fats',
      'Extra virgin olive oil (500ml) - anti-inflammatory',
      'Raw almonds (300g) - vitamin E, magnesium',
      'Walnuts (200g) - omega-3 ALA, brain health',
      'Natural nut butter (250g) - convenient fat + protein',
      'Chia seeds (200g) - omega-3, fiber, protein',
      'Ground flaxseed (200g) - lignans, omega-3'
    ]
  }
  
  // Essential pantry items
  list.pantry.items = [
    'Sea salt or Himalayan salt - mineral balance',
    'Black pepper, turmeric, ginger - anti-inflammatory spices',
    'Garlic (1 bulb) + onions (1kg) - flavor base, prebiotics',
    'Apple cider vinegar (500ml) - blood sugar support',
    'Coconut aminos or tamari - umami flavor enhancer',
    'Fresh herbs: basil, cilantro, parsley - antioxidants + flavor',
    'Lemons (6) + limes (4) - vitamin C, natural flavor enhancer',
    'Ceylon cinnamon - blood sugar regulation, natural sweetness'
  ]
  
  return {
    ...list,
    totalEstimatedCost: calculateEstimatedCost(list),
    shoppingTips: [
      'Shop the perimeter of the store first for whole foods',
      'Buy organic for the "Dirty Dozen" produce items when possible',
      'Batch prep proteins and grains on Sunday for the week',
      'Store leafy greens with a paper towel to extend freshness',
      `Focus on ${data.goal === 'lose_weight' ? 'volume foods that fill you up' : data.goal === 'build_muscle' ? 'calorie-dense, nutrient-rich options' : 'balanced portions across all food groups'}`
    ]
  }
}

function calculateEstimatedCost(list: any): string {
  // Rough cost estimation based on average prices
  const proteinCost = list.proteins.items.length * 8 // $8 average per protein item
  const veggieCost = list.vegetables.items.length * 3 // $3 average per vegetable item
  const grainCost = list.grains.items.length * 4 // $4 average per grain item
  const fatCost = list.fats.items.length * 6 // $6 average per fat item
  const pantryCost = list.pantry.items.length * 2 // $2 average per pantry item
  
  const total = proteinCost + veggieCost + grainCost + fatCost + pantryCost
  return `$${total}-${Math.round(total * 1.3)} USD (varies by location and quality)`
}

function generateEvidenceBasedSupplements(data: MealPlanRequest, aiInsights: any) {
  const age = data.age
  const sex = data.sex
  const goal = data.goal
  const isVegan = data.dietaryPreferences.includes('vegan') 
  const isVegetarian = data.dietaryPreferences.includes('vegetarian') || isVegan
  const bmi = data.weight / ((data.height / 100) ** 2)
  
  const supplements = []
  
  // Core supplements based on diet analysis
  if (isVegan) {
    supplements.push(
      {
        name: 'Vitamin B12 (Methylcobalamin)',
        priority: 'ESSENTIAL',
        reason: 'Vegans cannot obtain adequate B12 from plant foods alone. Deficiency leads to neurological damage and anemia.',
        dosage: '250-500mcg cyanocobalamin daily OR 2500mcg weekly',
        timing: 'With any meal for better absorption',
        evidence: 'Meta-analyses show 90%+ of vegans are B12 deficient without supplementation',
        interactions: 'None significant. Do not take with hot beverages.'
      },
      {
        name: 'Algae-Based Omega-3 (DHA/EPA)',
        priority: 'HIGH',
        reason: 'Plant-based diets typically lack preformed omega-3s (DHA/EPA) found in fish.',
        dosage: '300-500mg combined DHA/EPA daily',
        timing: 'With fat-containing meal for absorption',
        evidence: 'Vegan omega-3 status is significantly lower; algae supplements effectively raise levels',
        interactions: 'May enhance effects of blood-thinning medications'
      },
      {
        name: 'Iron (if female <50 or signs of deficiency)',
        priority: sex === 'female' && age < 50 ? 'HIGH' : 'MODERATE',
        reason: 'Plant-based iron (non-heme) is less bioavailable than heme iron from meat.',
        dosage: '18mg daily for premenopausal women, 8mg for men/postmenopausal women',
        timing: 'Away from tea/coffee, with vitamin C foods',
        evidence: 'Vegetarians have lower iron stores; supplementation recommended for high-risk groups',
        interactions: 'Reduces absorption of zinc and certain antibiotics'
      }
    )
  }
  
  // Goal-specific supplements
  if (goal === 'build_muscle' || data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high') {
    supplements.push(
      {
        name: 'Creatine Monohydrate',
        priority: 'HIGH',
        reason: 'Most researched supplement for strength and power. Increases muscle phosphocreatine stores.',
        dosage: '3-5g daily, timing irrelevant',
        timing: 'Anytime, preferably consistent daily timing',
        evidence: '70+ studies show 5-15% strength gains, faster recovery, increased muscle mass',
        interactions: 'Generally safe, may increase water retention initially'
      },
      {
        name: `${isVegan ? 'Plant' : 'Whey'} Protein Powder`,
        priority: 'MODERATE',
        reason: `Convenient way to reach ${data.proteinGrams}g daily protein target for muscle protein synthesis.`,
        dosage: '25-40g per serving, 1-2 servings daily if needed',
        timing: 'Post-workout within 2 hours, or between meals',
        evidence: 'Protein intake of 1.6-2.2g/kg bodyweight optimizes muscle protein synthesis',
        interactions: 'None significant, but space away from fiber-rich meals'
      }
    )
  }
  
  if (goal === 'lose_weight') {
    supplements.push(
      {
        name: 'Caffeine (if not sensitive)',
        priority: 'MODERATE',
        reason: 'Increases metabolic rate by 3-11%, enhances fat oxidation during exercise.',
        dosage: '200-400mg daily (equivalent to 2-4 cups coffee)',
        timing: '30-60 minutes before workouts, avoid after 2 PM',
        evidence: 'Meta-analyses show modest weight loss effects when combined with diet and exercise',
        interactions: 'May increase anxiety in sensitive individuals, affects sleep'
      },
      {
        name: 'Fiber Supplement (Psyllium Husk)',
        priority: 'MODERATE',
        reason: 'Increases satiety, helps maintain regular digestion during calorie restriction.',
        dosage: '5-10g with large glass of water, 30 minutes before meals',
        timing: 'Before largest meals, ensure adequate water intake',
        evidence: 'Soluble fiber supplements reduce appetite and support weight management',
        interactions: 'Can reduce absorption of medications - take 1 hour apart'
      }
    )
  }
  
  // Age and sex-specific supplements
  if (sex === 'female' && age < 50) {
    supplements.push({
      name: 'Iron (if signs of deficiency)',
      priority: 'MODERATE',
      reason: 'Premenopausal women lose iron through menstruation, higher risk of deficiency.',
      dosage: '18mg daily, or as directed by blood test results',
      timing: 'On empty stomach with vitamin C, away from calcium',
      evidence: 'Women of childbearing age have 2-5x higher iron deficiency rates',
      interactions: 'Can cause GI upset, reduces zinc absorption'
    })
  }
  
  if (age > 50) {
    supplements.push(
      {
        name: 'Vitamin D3',
        priority: 'HIGH',
        reason: 'Reduced skin synthesis with age, crucial for bone health and immune function.',
        dosage: '1000-2000 IU daily (get blood test to optimize)',
        timing: 'With fat-containing meal for absorption',
        evidence: 'Majority of older adults are vitamin D insufficient (<30 ng/mL)',
        interactions: 'Enhances calcium absorption, may affect certain heart medications'
      },
      {
        name: 'Vitamin B12',
        priority: 'HIGH',
        reason: 'Reduced stomach acid production with age impairs B12 absorption from food.',
        dosage: '25-100mcg daily or 1000mcg weekly',
        timing: 'With any meal',
        evidence: '10-30% of older adults have B12 malabsorption from food sources',
        interactions: 'None significant'
      }
    )
  }
  
  // Universal considerations
  if (data.dailyActivityLevel === 'very_active' || data.dailyActivityLevel === 'extremely_active') {
    supplements.push({
      name: 'Magnesium Glycinate',
      priority: 'MODERATE',
      reason: 'Intense training increases magnesium losses through sweat, needed for muscle function.',
      dosage: '200-400mg daily',
      timing: 'Evening, may promote relaxation and sleep',
      evidence: 'Athletes commonly have suboptimal magnesium status, supplementation improves performance',
      interactions: 'May enhance effects of blood pressure medications'
    })
  }
  
  // Add general multivitamin if no specific supplements recommended
  if (supplements.length === 0) {
    supplements.push({
      name: 'High-Quality Multivitamin',
      priority: 'LOW',
      reason: 'Insurance against potential micronutrient gaps in your personalized diet plan.',
      dosage: 'As directed on label, typically 1-2 capsules daily',
      timing: 'With breakfast for consistency',
      evidence: 'May help fill small nutritional gaps, but whole foods are preferred',
      interactions: 'Generally safe, but check individual vitamin levels'
    })
  }
  
  return {
    recommendations: supplements,
    importantNotes: [
      'Supplements complement, never replace, a well-planned diet',
      'Consider blood testing before starting iron, B12, or vitamin D',
      'Start supplements one at a time to identify any adverse reactions',
      'Consult healthcare provider if taking medications or have health conditions',
      `Your ${data.goal.replace('_', ' ')} goal and ${isVegan ? 'vegan' : isVegetarian ? 'vegetarian' : 'omnivorous'} diet inform these specific recommendations`
    ],
    totalMonthlyCost: calculateSupplementCost(supplements)
  }
}

function calculateSupplementCost(supplements: any[]): string {
  const costMap: { [key: string]: number } = {
    'Vitamin B12': 8,
    'Algae-Based Omega-3': 25,
    'Iron': 12,
    'Creatine Monohydrate': 15,
    'Protein Powder': 35,
    'Caffeine': 10,
    'Fiber Supplement': 15,
    'Vitamin D3': 10,
    'Magnesium Glycinate': 18,
    'High-Quality Multivitamin': 20
  }
  
  const totalCost = supplements.reduce((sum, supp) => {
    const baseName = supp.name.split(' ')[0] + ' ' + supp.name.split(' ')[1] || supp.name.split(' ')[0]
    return sum + (costMap[baseName] || 15)
  }, 0)
  
  return `$${totalCost}-${Math.round(totalCost * 1.4)}/month`
}

function generatePersonalizedHydration(data: MealPlanRequest) {
  const baseWater = data.waterIntake
  const exerciseBonus = data.exerciseIntensity === 'very_high' ? 500 : 
                      data.exerciseIntensity === 'high' ? 300 : 
                      data.exerciseIntensity === 'moderate' ? 200 : 100
  const climateAdjustment = 0 // Could be expanded for user location
  const totalWater = baseWater + exerciseBonus + climateAdjustment
  
  const morningAmount = Math.round(totalWater * 0.25)
  const preWorkoutAmount = Math.round(totalWater * 0.15)
  const duringWorkoutAmount = data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high' ? 
    Math.round(totalWater * 0.2) : Math.round(totalWater * 0.1)
  const postWorkoutAmount = Math.round(totalWater * 0.2)
  const remainingAmount = totalWater - morningAmount - preWorkoutAmount - duringWorkoutAmount - postWorkoutAmount
  const mealAmount = Math.round(remainingAmount / data.mealsPerDay)
  
  return {
    dailyTarget: totalWater,
    schedule: [
      {
        time: 'Upon waking (6:00-7:00 AM)',
        amount: `${morningAmount}ml`,
        type: 'Plain water with lemon slice',
        purpose: 'Rehydrate after overnight fast, kickstart metabolism',
        tip: 'Keep a glass by your bedside to start immediately'
      },
      {
        time: '30-60 minutes before workout',
        amount: `${preWorkoutAmount}ml`,
        type: 'Plain water or diluted electrolyte drink',
        purpose: 'Ensure optimal hydration for performance',
        tip: 'Stop drinking 15 minutes before exercise to avoid discomfort'
      },
      {
        time: 'During workout (if >45 minutes)',
        amount: `${duringWorkoutAmount}ml`,
        type: data.exerciseIntensity === 'very_high' ? 'Electrolyte drink' : 'Plain water',
        purpose: 'Maintain hydration and electrolyte balance',
        tip: 'Sip small amounts every 15-20 minutes during exercise'
      },
      {
        time: 'Within 30 minutes post-workout',
        amount: `${postWorkoutAmount}ml`,
        type: 'Water + pinch of sea salt or coconut water',
        purpose: 'Rapid rehydration and electrolyte replacement',
        tip: 'Weigh yourself before/after exercise - drink 150% of weight lost'
      },
      {
        time: 'With each meal',
        amount: `${mealAmount}ml`,
        type: 'Room temperature water',
        purpose: 'Aid digestion and nutrient absorption',
        tip: 'Drink mostly before and after meals, limit during eating'
      },
      {
        time: 'Evening wind-down (2-3 hours before bed)',
        amount: '200-300ml',
        type: 'Herbal tea (chamomile, peppermint) or warm water',
        purpose: 'Relaxation and final hydration without disrupting sleep',
        tip: 'Avoid large amounts close to bedtime to prevent night wakings'
      }
    ],
    hydrationTips: [
      `Your ${data.goal.replace('_', ' ')} goal benefits from optimal hydration for ${data.goal === 'lose_weight' ? 'appetite control and metabolism' : 
        data.goal === 'build_muscle' ? 'nutrient transport and recovery' : 
        data.goal === 'athletic_performance' ? 'performance and thermoregulation' : 'overall health optimization'}`,
      'Monitor urine color: aim for pale yellow throughout the day',
      `Track intake with a ${Math.round(totalWater/4)}ml bottle - fill and finish 4 times daily`,
      'Add natural flavor with cucumber, mint, or berries if plain water is boring',
      data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high' ? 
        'Consider electrolyte replacement during intense or long workouts (>1 hour)' : 
        'Plain water is sufficient for moderate exercise sessions',
      'Increase intake on hot days, when ill, or if consuming alcohol/caffeine'
    ],
    warning: totalWater > 4000 ? 
      'This is a high water target. Increase gradually and ensure electrolyte balance.' : 
      null
  }
}

function generateContextualMealPrepTips(data: MealPlanRequest, aiInsights: any) {
  const tips = {
    weekly: {
      title: 'WEEKLY PREP STRATEGY',
      items: [
        `Sunday Prep Session (2-3 hours): Batch cook ${Math.round(data.proteinGrams * 7 / 25)} portions of protein for the week`,
        'Wash, chop, and portion vegetables immediately after grocery shopping',
        `Cook ${data.dietaryPreferences.includes('keto') ? 'cauliflower rice and zucchini noodles' : 'grains like quinoa, brown rice in large batches'}`,
        'Prepare mason jar salads - dressing on bottom, sturdy vegetables, greens on top',
        data.mealsPerDay >= 5 ? 'Pre-portion snacks into grab-and-go containers' : 'Prepare 2-3 backup meals for busy days'
      ]
    },
    daily: {
      title: 'DAILY EFFICIENCY TIPS',
      items: [
        `Morning: Set out tomorrow's meals during breakfast cleanup`,
        'Use slow cooker or Instant Pot for hands-off protein cooking',
        `Evening: Quick 15-minute prep for next day's ${data.mealsPerDay} meals`,
        data.exerciseIntensity === 'high' || data.exerciseIntensity === 'very_high' ? 
          'Pack post-workout meal/shake before leaving for gym' : 
          'Prepare healthy snacks to avoid impulsive food choices',
        'Keep a "emergency meal" kit: canned fish, quick oats, nuts, frozen vegetables'
      ]
    },
    storage: {
      title: 'SMART STORAGE SOLUTIONS',
      items: [
        `Glass containers (${data.mealsPerDay + 2} containers recommended) - microwave safe, no plastic chemicals`,
        'Vacuum seal or freezer bags for batch-cooked proteins (stay fresh 3+ months)',
        'Herb storage: wash, dry completely, store in glass jars with paper towel',
        'Avocado hack: store cut avocado with onion slice to prevent browning',
        data.dietaryPreferences.includes('vegan') ? 
          'Soak nuts/seeds overnight for better digestibility and faster cooking' :
          'Raw proteins: use within 2 days or freeze immediately after purchase'
      ]
    },
    goalSpecific: {
      title: `${data.goal.replace('_', ' ').toUpperCase()} OPTIMIZATION`,
      items: data.goal === 'lose_weight' ? [
        'Pre-portion all meals to avoid overeating - use smaller containers',
        'Prep high-volume, low-calorie foods: vegetable soups, large salads',
        'Keep cut vegetables visible in fridge front for easy snacking',
        'Freeze single-serving smoothie packets with pre-measured ingredients'
      ] : data.goal === 'build_muscle' ? [
        'Double batch and freeze protein-rich casseroles and stews',
        `Always have quick protein available: hard-boiled eggs, Greek yogurt, protein powder`,
        'Pre-make calorie-dense smoothie ingredients in freezer bags',
        'Prep post-workout meals in advance - timing is crucial for muscle synthesis'
      ] : data.goal === 'athletic_performance' ? [
        'Prepare both pre and post-workout meals with optimal carb timing',
        'Batch cook sweet potatoes and oats for quick energy sources',
        'Make electrolyte popsicles for post-workout recovery',
        'Keep emergency energy foods: dates, bananas, homemade energy balls'
      ] : [
        'Focus on variety - prep different cuisines to avoid boredom',
        'Batch cook versatile bases: roasted vegetables, cooked grains, proteins',
        'Prepare healthy versions of comfort foods for cravings',
        'Keep backup healthy options for social situations'
      ]
    },
    timeHacks: {
      title: 'TIME-SAVING HACKS',
      items: [
        `Use ${data.exerciseIntensity === 'high' ? 'protein powder' : 'quick-cooking proteins like eggs, canned fish'} for 5-minute meals`,
        'One-pan meals: protein + vegetables + healthy fat cooked together',
        'Spiralize vegetables while watching TV for weekly prep',
        'Use pre-washed salad mixes strategically (more expensive but saves 30+ minutes weekly)',
        'Make double portions at dinner - instant lunch for tomorrow',
        data.dietaryPreferences.includes('keto') ? 
          'Keep keto emergency kit: nuts, cheese, olives, avocados' :
          'Master 3-ingredient meals: protein + vegetable + healthy carb/fat'
      ]
    }
  }
  
  return {
    ...tips,
    weeklyTimeInvestment: '2-3 hours prep saves 1+ hour daily during busy weekdays',
    budgetImpact: 'Meal prep reduces food waste by 40% and dining out by 60%',
    successTip: `Consistency beats perfection - even prepping ${Math.ceil(data.mealsPerDay/2)} meals ahead makes a huge difference`
  }
}

function generateMockMealPlan(data: MealPlanRequest) {
  const aiInsights = {
    recommendedFoods: [],
    avoidFoods: [],
    keyNutrients: [],
    mealTiming: '',
    specialConsiderations: []
  }
  
  return {
    weeklyPlan: [
      {
        day: 1,
        meals: generatePersonalizedMeals('', data, aiInsights)
      }
    ],
    recipes: generateIntelligentRecipes('', data, aiInsights),
    shoppingList: generateSmartShoppingList(data, aiInsights),
    supplements: generateEvidenceBasedSupplements(data, aiInsights),
    hydrationSchedule: generatePersonalizedHydration(data),
    mealPrepTips: generateContextualMealPrepTips(data, aiInsights),
    foodCategorization: generateFoodCategorization(data, aiInsights),
    nutritionalAnalysis: generateNutritionalAnalysis(data, aiInsights),
    aiGenerated: false,
    aiConfidence: 'fallback',
    generatedText: 'Using intelligent fallback recommendations based on your profile'
  }
}